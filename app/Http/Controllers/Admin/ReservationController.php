<?php
// app/Http/Controllers/Admin/ReservationController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    /**
     * Display the reservations list.
     *
     * Only Pending and Rejected are shown in this list — Approved and
     * Expired reservations live in the History page. Both are still
     * counted in the metrics so admins see the full picture.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status']);

        $reservations = Reservation::filter($filters)
            ->whereNotIn('status', ['Approved', 'Expired'])
            ->latest()
            ->get();

        $metrics = [
            'total'    => Reservation::count(),
            'pending'  => Reservation::pending()->count(),
            'approved' => Reservation::approved()->count(),
            'rejected' => Reservation::rejected()->count(),
            'expired'  => Reservation::expired()->count(),
        ];

        $reservationsForReact = $reservations->mapWithKeys(function ($res) {
            return [$res->reference_no => $res->toReactArray()];
        })->toArray();

        return view('admin.reservations', compact(
            'reservations',
            'metrics',
            'filters',
            'reservationsForReact'
        ));
    }

    /**
     * Store a newly created reservation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'          => ['nullable', 'exists:users,id'],
            'room_id'          => ['nullable', 'exists:rooms,id'],
            'tenant_name'      => ['required', 'string', 'max:120'],
            'email'            => ['required', 'email', 'max:120'],
            'phone'            => ['required', 'string', 'max:20'],
            'desired_room'     => ['required', 'string', 'max:120'],
            'room_bed_id'      => ['nullable', 'string', 'max:50'],
            'check_in'         => ['required', 'date'],
            'check_out'        => ['nullable', 'date', 'after:check_in'],
            'move_in'          => ['nullable', 'date'],
            'total_charges'    => ['required', 'numeric', 'min:0'],
            'reservation_fee'  => ['nullable', 'numeric', 'min:0'],
            'payment_option'   => ['nullable', Rule::in(['Down Payment', 'Full Payment'])],
            'payment_method'   => ['nullable', Rule::in(['Not Set', 'GCash', 'Over The Counter', 'Bank Transfer'])],
            'remaining'        => ['nullable', 'numeric', 'min:0'],
            'request_details'  => ['nullable', 'string'],
        ]);

        $avatar = Reservation::makeAvatar($validated['tenant_name']);

        $reservation = Reservation::create([
            ...$validated,
            'reference_no'       => Reservation::generateReferenceNo(),
            'reservation_number' => Reservation::generateReservationNumber(),
            'tenant_initial'     => $avatar['initial'],
            'avatar_bg'          => $avatar['bg'],
            'avatar_color'       => $avatar['color'],
            'reservation_fee'    => $validated['reservation_fee'] ?? 500,
            'payment_option'     => $validated['payment_option'] ?? 'Down Payment',
            'payment_method'     => $validated['payment_method'] ?? 'GCash',
            'remaining'          => $validated['remaining'] ?? 0,
            'status'             => 'Pending',
        ]);

        AuditLogger::log([
            'action'       => 'CREATE',
            'module'       => 'RESERVATIONS',
            'description'  => 'Created reservation ' . $reservation->reference_no
                . ' for ' . $reservation->tenant_name,
            'subject_type' => Reservation::class,
            'subject_id'   => $reservation->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reservation created successfully.',
            'data'    => $reservation->toReactArray(),
        ]);
    }

    /**
     * Display the specified reservation.
     */
    public function show(Reservation $reservation)
    {
        return response()->json([
            'success' => true,
            'data'    => $reservation->toReactArray(),
        ]);
    }

    /**
     * Update the specified reservation (admin notes, status).
     */
    public function update(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
            'status'      => ['sometimes', Rule::in(['Pending', 'Approved', 'Rejected', 'Expired'])],
        ]);

        if (isset($validated['status']) && in_array($validated['status'], ['Approved', 'Rejected'])) {
            $validated['processed_by'] = $request->user()->id;
            $validated['processed_at'] = now();
        }

        $reservation->update($validated);

        AuditLogger::log([
            'action'       => 'UPDATE',
            'module'       => 'RESERVATIONS',
            'description'  => 'Updated reservation ' . $reservation->reference_no
                . ' (' . $reservation->tenant_name . ')',
            'subject_type' => Reservation::class,
            'subject_id'   => $reservation->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reservation updated successfully.',
            'data'    => $reservation->fresh()->toReactArray(),
        ]);
    }

    /**
     * Approve a reservation.
     *
     * Behaviour:
     *   • Reservation  → Approved  (always — regardless of invoice state)
     *   • Tenant       → created / refreshed
     *   • Room         → re-synced
     *   • Invoice tied to this reservation:
     *       - If already Paid → do nothing (money side settled elsewhere).
     *       - Otherwise, derive the correct state from what the tenant
     *         actually paid (reservation_fee vs total_charges):
     *           reservation_fee >= total_charges        → Paid
     *           0 < reservation_fee < total_charges     → Partial
     *           reservation_fee == 0                    → Unpaid
     */
    public function approve(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
        ]);

        // ── Re-validate bed availability at approval time ──
        if (
            in_array($reservation->room?->room_type, ['Bedspace', 'Barkada Room'], true)
            && $reservation->room_bed_id
        ) {

            $in  = $reservation->check_in;
            $out = $reservation->check_out;

            $conflict = Reservation::where('room_id', $reservation->room_id)
                ->where('room_bed_id', $reservation->room_bed_id)
                ->where('id', '!=', $reservation->id)
                ->where('status', 'Approved')
                ->when($in && $out, function ($q) use ($in, $out) {
                    $q->whereDate('check_in', '<', $out->toDateString())
                        ->where(function ($q2) use ($in) {
                            $q2->whereDate('check_out', '>', $in->toDateString())
                                ->orWhereNull('check_out');
                        });
                })
                ->exists();

            if ($conflict) {
                return response()->json([
                    'message' => 'That bed slot was approved for another reservation. Reject this one or ask the tenant to pick a different bed.',
                ], 422);
            }
        }


        DB::transaction(function () use ($request, $reservation, $validated) {
            // 1. Approve the reservation — this always happens, independent
            //    of how much the tenant has paid.
            $reservation->update([
                'status'       => 'Approved',
                'admin_notes'  => $validated['admin_notes'] ?? $reservation->admin_notes,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);

            // 2. Create / refresh the tenant.
            $tenant = null;
            if ($reservation->user_id) {
                $tenant = Tenant::updateOrCreate(
                    ['user_id' => $reservation->user_id],
                    [
                        'room_id'                 => $reservation->room_id,
                        'approved_reservation_id' => $reservation->id,
                        'rent_rate'               => $reservation->total_charges,
                        'status'                  => 'active',
                    ]
                );
            }

            // 3. Re-sync the room.
            if ($reservation->room) {
                $reservation->room->syncStatus();
            }

            // 4. Reconcile the invoice against what was actually paid.
            $this->settleReservationInvoice($reservation, $tenant);
        });

        AuditLogger::log([
            'action'       => 'APPROVE',
            'module'       => 'RESERVATIONS',
            'description'  => 'Approved reservation ' . $reservation->reference_no
                . ' for ' . $reservation->tenant_name
                . ' (' . $reservation->desired_room . ')',
            'subject_type' => Reservation::class,
            'subject_id'   => $reservation->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reservation approved.',
            'data'    => $reservation->fresh()->toReactArray(),
        ]);
    }

    /**
     * Reject a reservation.
     *
     * Deletes any Unpaid / Partial / Pending invoice tied to this
     * reservation (and its receipt file). Paid invoices are kept.
     */
    public function reject(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $reservation, $validated) {
            $reservation->update([
                'status'       => 'Rejected',
                'admin_notes'  => $validated['admin_notes'] ?? $reservation->admin_notes,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);

            if ($reservation->room) {
                $reservation->room->syncStatus();
            }

            $this->deleteInvoicesOnRejection($reservation);
        });

        AuditLogger::log([
            'action'       => 'REJECT',
            'module'       => 'RESERVATIONS',
            'description'  => 'Rejected reservation ' . $reservation->reference_no
                . ' for ' . $reservation->tenant_name
                . ($validated['admin_notes'] ? ' (' . $validated['admin_notes'] . ')' : ''),
            'status'       => 'warning',
            'subject_type' => Reservation::class,
            'subject_id'   => $reservation->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reservation rejected.',
            'data'    => $reservation->fresh()->toReactArray(),
        ]);
    }

    /**
     * Remove the specified reservation.
     */
    public function destroy(Reservation $reservation)
    {
        AuditLogger::log([
            'action'       => 'DELETE',
            'module'       => 'RESERVATIONS',
            'description'  => 'Deleted reservation ' . $reservation->reference_no
                . ' (' . $reservation->tenant_name . ')',
            'status'       => 'warning',
            'subject_type' => Reservation::class,
            'subject_id'   => $reservation->id,
        ]);

        $reservation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reservation deleted successfully.',
        ]);
    }

    // ============================================================
    // HELPERS
    // ============================================================

    /**
     * Reconcile the invoice tied to a reservation against what the
     * tenant actually paid.
     *
     * The invoice status is derived purely from `reservation_fee` vs
     * `total_charges`:
     *
     *   paid >= total  → Paid    (balance = 0)
     *   paid >  0      → Partial (balance = total - paid)
     *   paid == 0      → Unpaid  (balance = total)
     *
     * The reservation itself is always Approved — that decision is
     * made in `approve()` and is independent of the invoice state.
     *
     * Exits early (does nothing) if an invoice for this reservation is
     * already Paid — the money side has already been settled from the
     * Billing page, and we must not create a duplicate.
     */
    private function settleReservationInvoice(Reservation $reservation, ?Tenant $tenant): void
    {
        $totalCharges = (float) $reservation->total_charges;
        $paidAmount   = (float) $reservation->reservation_fee;
        $remaining    = max(0, $totalCharges - $paidAmount);

        // Derive the correct invoice status from what was paid.
        [$status, $balance] = match (true) {
            $paidAmount <= 0            => ['Unpaid',  $totalCharges],
            $paidAmount + 0.005 >= $totalCharges
            => ['Paid',    0.0],
            default                     => ['Partial', $remaining],
        };

        // ── Guard: if any invoice for this reservation is already Paid,
        //    the money side was handled elsewhere (Billing page). Do
        //    nothing on the invoice side.
        $alreadyPaid = Invoice::query()
            ->where('description', 'like', '%' . $reservation->reference_no . '%')
            ->where('status', 'Paid')
            ->exists();

        if ($alreadyPaid) {
            return;
        }

        // ── Find the existing invoice (if any) for this reservation.
        $invoice = Invoice::query()
            ->where('description', 'like', '%' . $reservation->reference_no . '%')
            ->latest()
            ->first();

        // ── Case A: no invoice yet — create one at the correct state.
        if (! $invoice) {
            Invoice::create([
                'transaction_no' => Invoice::generateTransactionNo(),
                'tenant_id'      => $tenant?->id,
                'room_id'        => $reservation->room_id,
                'tenant_name'    => $reservation->tenant_name,
                'room_number'    => $reservation->room?->room_number
                    ?? $reservation->desired_room,
                'description'    => $status === 'Paid'
                    ? 'Reservation Payment — ' . $reservation->reference_no
                    : 'Remaining Balance — Reservation ' . $reservation->reference_no,
                'check_in'       => $reservation->check_in,
                'check_out'      => $reservation->check_out,
                'rate_per_day'   => (float) ($reservation->room?->daily_rent ?? 0),
                'days'           => $reservation->stay_duration ?? 1,
                'total_amount'   => $totalCharges,
                'amount_paid'    => $paidAmount,
                'balance'        => $balance,
                'due_date'       => $status === 'Unpaid'
                    ? now()->addDays(7)->toDateString()
                    : $reservation->check_in,
                'payment_method' => $reservation->payment_method ?? 'Not Set',
                'status'         => $status,
                'date_paid'      => $status !== 'Unpaid' ? now()->toDateString() : null,
                'verified_by'    => $status === 'Paid' ? Auth::id() : null,
                'verified_at'    => $status === 'Paid' ? now() : null,
            ]);
            return;
        }

        // ── Case B: invoice exists — update it to match the paid amount.
        $invoice->update([
            'total_amount' => $totalCharges,
            'amount_paid'  => $paidAmount,
            'balance'      => $balance,
            'status'       => $status,
            'date_paid'    => $status !== 'Unpaid'
                ? ($invoice->date_paid ?? now()->toDateString())
                : null,
            'verified_by'  => $status === 'Paid'
                ? ($invoice->verified_by ?? Auth::id())
                : null,
            'verified_at'  => $status === 'Paid'
                ? ($invoice->verified_at ?? now())
                : null,
        ]);
    }

    /**
     * Delete any non-paid invoice tied to this reservation (used on reject).
     */
    private function deleteInvoicesOnRejection(Reservation $reservation): void
    {
        $invoices = Invoice::query()
            ->where('description', 'like', '%' . $reservation->reference_no . '%')
            ->whereIn('status', ['Unpaid', 'Partial', 'Pending'])
            ->get();

        foreach ($invoices as $inv) {
            if ($inv->receipt_path && Storage::disk('public')->exists($inv->receipt_path)) {
                Storage::disk('public')->delete($inv->receipt_path);
            }
            $inv->delete();
        }
    }
}
