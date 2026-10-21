<?php
// app/Http/Controllers/Admin/ReservationController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    /**
     * Display the reservations list.
     *
     * Only reservations that still need admin attention are shown:
     *   - Pending  → awaiting approval/rejection
     *   - Rejected → admin may want to revisit / follow up
     *
     * Approved reservations are hidden because they've already been
     * processed and now live in the History page as active tenants.
     * Expired reservations are hidden because they're stale.
     *
     * Both are still counted in the metrics so admins see the full picture.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status']);

        // Hide Approved + Expired — they live in History now.
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

        return view('admin.reservations', compact('reservations', 'metrics', 'filters', 'reservationsForReact'));
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

        return response()->json([
            'success' => true,
            'message' => 'Reservation updated successfully.',
            'data'    => $reservation->fresh()->toReactArray(),
        ]);
    }

    /**
     * Approve a reservation.
     *
     * - Creates / refreshes the Tenant record.
     * - Does NOT force the room to "Reserved"; instead we let Room::syncStatus()
     *   decide the status based on ACTUAL occupancy:
     *      occupied < total_beds → Available
     *      occupied >= total_beds → Reserved
     * - Creates an "Unpaid" invoice for the reservation's `remaining`
     *   balance so the tenant sees it on their dashboard.
     */
    public function approve(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $reservation, $validated) {
            // 1. Approve the reservation BEFORE creating the tenant.
            $reservation->update([
                'status'       => 'Approved',
                'admin_notes'  => $validated['admin_notes'] ?? $reservation->admin_notes,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);

            // 2. Create / refresh the tenant link.
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

            // 3. Recompute the room status based on real occupancy.
            if ($reservation->room) {
                $reservation->room->syncStatus();
            }

            // 4. Auto-create an Unpaid invoice for the remaining balance,
            //    unless one already exists for this reservation.
            $this->createRemainingBalanceInvoice($reservation, $tenant);
        });

        return response()->json([
            'success' => true,
            'message' => 'Reservation approved.',
            'data'    => $reservation->fresh()->toReactArray(),
        ]);
    }

    /**
     * Reject a reservation.
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

            // Room status may have been set to "Reserved" earlier — re-sync it.
            if ($reservation->room) {
                $reservation->room->syncStatus();
            }
        });

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
        $reservation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reservation deleted successfully.',
        ]);
    }

    /**
     * Create an Unpaid invoice for the reservation's remaining balance.
     *
     * The invoice's description includes both "Remaining" and the
     * reservation reference number, so the dashboard's dedup check
     * (description LIKE '%remaining%' OR '%reservation%') recognises
     * it and won't double-count the reservation remaining.
     */
    private function createRemainingBalanceInvoice(Reservation $reservation, ?Tenant $tenant): void
    {
        // Nothing to bill.
        if ((float) $reservation->remaining <= 0) {
            return;
        }

        // Skip if we already created one for this reservation.
        $alreadyBilled = Invoice::where('tenant_id', $tenant?->id)
            ->where(function ($q) use ($reservation) {
                $q->where('description', 'like', '%' . $reservation->reference_no . '%')
                  ->orWhere('description', 'like', '%remaining%');
            })
            ->exists();

        if ($alreadyBilled) {
            return;
        }

        $roomNumber = $reservation->room?->room_number
            ?? $reservation->desired_room
            ?? 'N/A';

        $days = $reservation->stay_duration ?? 1;
        $rate = (float) ($reservation->room?->daily_rent
            ?? ($reservation->total_charges / max($days, 1)));

        Invoice::create([
            'transaction_no' => Invoice::generateTransactionNo(),
            'tenant_id'      => $tenant?->id,
            'room_id'        => $reservation->room_id,
            'tenant_name'    => $reservation->tenant_name,
            'room_number'    => $roomNumber,
            'description'    => 'Remaining Balance — Reservation ' . $reservation->reference_no,
            'check_in'       => $reservation->check_in,
            'check_out'      => $reservation->check_out,
            'rate_per_day'   => $rate,
            'days'           => $days,
            'total_amount'   => (float) $reservation->total_charges,
            'amount_paid'    => (float) $reservation->reservation_fee,
            'balance'        => (float) $reservation->remaining,
            'due_date'       => now()->addDays(7)->toDateString(),
            'payment_method' => $reservation->payment_method ?? 'Not Set',
            'status'         => 'Unpaid',
        ]);
    }
}