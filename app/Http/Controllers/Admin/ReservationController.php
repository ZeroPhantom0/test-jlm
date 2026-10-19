<?php
// app/Http/Controllers/Admin/ReservationController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    /**
     * Display the reservations list.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status']);

        $reservations = Reservation::filter($filters)
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
     * On approval:
     *   1. The reserved room is marked as "Reserved".
     *   2. A `tenants` record is created for the reserving user — this is
     *      the ONLY point where a user becomes a Tenant.
     *   3. The reservation status flips to "Approved".
     *
     * IMPORTANT: The `tenants` table stores the same dates the reservation
     * holds — but under `move_in_*` / `move_out_*` naming. So we copy
     * BOTH sides (check-in → move_in, check-out → move_out) here.
     *
     * Steps 1–3 are wrapped in a transaction so a partial failure can't
     * leave a room "Reserved" without an approved reservation.
     */
    public function approve(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $reservation, $validated) {
            // 1. Mark the reserved room
            if ($reservation->room_id) {
                $reservation->room?->update([
                    'status'      => 'Reserved',
                    'tenant_name' => $reservation->tenant_name,
                ]);
            }

            // 2. Approve the reservation BEFORE creating the tenant,
            //    so the FK points at a valid (now-approved) reservation.
            $reservation->update([
                'status'       => 'Approved',
                'admin_notes'  => $validated['admin_notes'] ?? $reservation->admin_notes,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);

            // 3. Create (or refresh) the tenant link. Only ONE row per user;
            //    all lease dates are now read from this reservation.
            if ($reservation->user_id) {
                Tenant::updateOrCreate(
                    ['user_id' => $reservation->user_id],
                    [
                        'room_id'                 => $reservation->room_id,
                        'approved_reservation_id' => $reservation->id,
                        'rent_rate'               => $reservation->total_charges,
                        'status'                  => 'active',
                    ]
                );
            }
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

        $reservation->update([
            'status'       => 'Rejected',
            'admin_notes'  => $validated['admin_notes'] ?? $reservation->admin_notes,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
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
        $reservation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reservation deleted successfully.',
        ]);
    }
}
