<?php
// app/Http/Controllers/Admin/ReservationController.php

namespace App\Http\Controllers\Admin;

use App\Events\InvoiceUpdated;
use App\Events\ReservationUpdated;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
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
     * Live data endpoint for polling / Echo refresh.
     * GET /admin/reservations/data
     */
    public function data(Request $request)
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

        $rows = $reservations->map(fn (Reservation $res) => [
            'id'                  => $res->id,
            'reference_no'        => $res->reference_no,
            'tenant_name'         => $res->tenant_name,
            'tenant_initial'      => $res->tenant_initial,
            'avatar_bg'           => $res->avatar_bg,
            'avatar_color'        => $res->avatar_color,
            'email'               => $res->email,
            'phone'               => $res->phone,
            'desired_room'        => $res->desired_room,
            'formatted_check_in'  => $res->formatted_check_in,
            'formatted_check_out' => $res->formatted_check_out,
            'check_out_is_null'   => is_null($res->check_out),
            'formatted_fee'       => $res->formatted_fee,
            'created_at'          => $res->created_at?->format('M d, Y'),
            'status'              => $res->status,
            'status_class'        => $res->status_class,
            'status_icon'         => $res->status_icon,
        ])->values();

        $reactMap = $reservations->mapWithKeys(
            fn (Reservation $res) => [$res->reference_no => $res->toReactArray()]
        )->toArray();

        return response()->json([
            'rows'    => $rows,
            'metrics' => $metrics,
            'react'   => $reactMap,
        ]);
    }

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

        event(new ReservationUpdated('created', $reservation->id));

        return response()->json([
            'success' => true,
            'message' => 'Reservation created successfully.',
            'data'    => $reservation->toReactArray(),
        ]);
    }

    public function show(Reservation $reservation)
    {
        return response()->json([
            'success' => true,
            'data'    => $reservation->toReactArray(),
        ]);
    }

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

        event(new ReservationUpdated('updated', $reservation->id));

        return response()->json([
            'success' => true,
            'message' => 'Reservation updated successfully.',
            'data'    => $reservation->fresh()->toReactArray(),
        ]);
    }

    public function approve(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
        ]);

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

        $invoiceChanged = false;

        DB::transaction(function () use ($request, $reservation, $validated, &$invoiceChanged) {
            $reservation->update([
                'status'       => 'Approved',
                'admin_notes'  => $validated['admin_notes'] ?? $reservation->admin_notes,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);

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

            if ($reservation->room) {
                $reservation->room->syncStatus();
            }

            $invoiceChanged = $this->settleReservationInvoice($reservation, $tenant);
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

        event(new ReservationUpdated('approved', $reservation->id));
        if ($invoiceChanged) {
            event(new InvoiceUpdated('created', null));
        }

        return response()->json([
            'success' => true,
            'message' => 'Reservation approved.',
            'data'    => $reservation->fresh()->toReactArray(),
        ]);
    }

    public function reject(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
        ]);

        $invoicesDeleted = 0;

        DB::transaction(function () use ($request, $reservation, $validated, &$invoicesDeleted) {
            $reservation->update([
                'status'       => 'Rejected',
                'admin_notes'  => $validated['admin_notes'] ?? $reservation->admin_notes,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);

            if ($reservation->room) {
                $reservation->room->syncStatus();
            }

            $invoicesDeleted = $this->deleteInvoicesOnRejection($reservation);
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

        event(new ReservationUpdated('rejected', $reservation->id));
        if ($invoicesDeleted > 0) {
            event(new InvoiceUpdated('deleted', null));
        }

        return response()->json([
            'success' => true,
            'message' => 'Reservation rejected.',
            'data'    => $reservation->fresh()->toReactArray(),
        ]);
    }

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

        event(new ReservationUpdated('deleted', $reservation->id));

        return response()->json([
            'success' => true,
            'message' => 'Reservation deleted successfully.',
        ]);
    }

    // ============================================================
    // HELPERS
    // ============================================================

    private function computeDueDate(?Carbon $checkIn, ?Carbon $checkOut): Carbon
    {
        if (! $checkIn) {
            return now()->addDays(7);
        }

        $out  = $checkOut ?: $checkIn->copy()->addDay();
        $days = max(1, $checkIn->diffInDays($out));

        if ($days <= 1) {
            return $out->copy();
        }
        if ($days <= 6) {
            return $checkIn->copy()->addDays(3);
        }
        if ($days <= 31) {
            return $checkIn->copy()->addDays(7);
        }
        if ($days <= 100) {
            return $checkIn->copy()->addMonths(1);
        }
        if ($days <= 364) {
            return $checkIn->copy()->addMonths(2);
        }
        return $checkIn->copy()->addMonths(3);
    }

    /**
     * Returns true if an invoice was created or updated (so callers can broadcast).
     */
    private function settleReservationInvoice(Reservation $reservation, ?Tenant $tenant): bool
    {
        $totalCharges = (float) $reservation->total_charges;
        $paidAmount   = (float) $reservation->reservation_fee;
        $remaining    = max(0, $totalCharges - $paidAmount);

        [$status, $balance] = match (true) {
            $paidAmount <= 0                       => ['Unpaid',  $totalCharges],
            $paidAmount + 0.005 >= $totalCharges   => ['Paid',    0.0],
            default                                => ['Partial', $remaining],
        };

        $alreadyPaid = Invoice::query()
            ->where('description', 'like', '%' . $reservation->reference_no . '%')
            ->where('status', 'Paid')
            ->exists();

        if ($alreadyPaid) {
            return false;
        }

        $invoice = Invoice::query()
            ->where('description', 'like', '%' . $reservation->reference_no . '%')
            ->latest()
            ->first();

        $dueDate = $this->computeDueDate(
            $reservation->check_in,
            $reservation->check_out
        );

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
                'due_date'       => $dueDate->toDateString(),
                'payment_method' => $reservation->payment_method ?? 'Not Set',
                'status'         => $status,
                'date_paid'      => $status !== 'Unpaid' ? now()->toDateString() : null,
                'verified_by'    => $status === 'Paid' ? Auth::id() : null,
                'verified_at'    => $status === 'Paid' ? now() : null,
            ]);
            return true;
        }

        $invoice->update([
            'total_amount' => $totalCharges,
            'amount_paid'  => $paidAmount,
            'balance'      => $balance,
            'status'       => $status,
            'due_date'     => $dueDate->toDateString(),
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

        return true;
    }

    /**
     * Returns how many invoices were deleted.
     */
    private function deleteInvoicesOnRejection(Reservation $reservation): int
    {
        $invoices = Invoice::query()
            ->where('description', 'like', '%' . $reservation->reference_no . '%')
            ->whereIn('status', ['Unpaid', 'Partial', 'Pending'])
            ->get();

        $count = 0;
        foreach ($invoices as $inv) {
            if ($inv->receipt_path && Storage::disk('public')->exists($inv->receipt_path)) {
                Storage::disk('public')->delete($inv->receipt_path);
            }
            $inv->delete();
            $count++;
        }
        return $count;
    }
}