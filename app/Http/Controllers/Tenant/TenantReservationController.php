<?php
// app/Http/Controllers/Tenant/TenantReservationController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\VirtualTour;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TenantReservationController extends Controller
{
    /**
     * Display the tenant's reservations page + available (toured) rooms.
     */
    public function index()
    {
        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        // ---------- My Reservations ----------
        $myReservations = Reservation::where(function ($q) use ($user, $tenant) {
                $q->where('user_id', $user->id)
                  ->orWhere('tenant_name', $user->name);
            })
            ->latest()
            ->get();

        // Format the list for the Blade cards
        $reservationCards = $myReservations->map(function ($r) {
            $statusClass = match ($r->status) {
                'Approved' => 'bg-emerald-100 text-emerald-600',
                'Rejected' => 'bg-rose-100 text-rose-600',
                'Expired'  => 'bg-slate-100 text-slate-500',
                default    => 'bg-amber-100 text-amber-700',
            };
            $statusIcon = match ($r->status) {
                'Approved' => 'fa-regular fa-circle-check',
                'Rejected' => 'fa-regular fa-circle-xmark',
                'Expired'  => 'fa-regular fa-clock',
                default    => 'fa-regular fa-clock',
            };

            return [
                'id'          => $r->id,
                'key'         => $r->reference_no,
                'room'        => $r->desired_room,
                'status'      => $r->status,
                'statusClass' => $statusClass,
                'statusIcon'  => $statusIcon,
                'move_in'     => $r->check_in?->format('M j, Y') ?? '—',
                'submitted'   => $r->created_at?->format('M j, Y') ?? '—',
                'deposit'     => number_format((float) $r->reservation_fee, 2),
                'note'        => $r->admin_notes,
            ];
        });

        // ---------- Available Rooms (only rooms WITH a published tour) ----------
        $rooms = Room::with(['virtualTour' => fn ($q) => $q->where('status', 'Published')])
            ->whereHas('virtualTour', fn ($q) => $q->where('status', 'Published'))
            ->orderBy('room_number')
            ->get()
            ->map(function (Room $room) {
                return [
                    'id'          => $room->id,
                    'num'         => $room->room_number,
                    'name'        => $room->room_type,
                    'price'       => (float) $room->daily_rent,
                    'status'      => $room->status,
                    'img'         => $room->virtualTour?->thumbnail_url
                                        ?? 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=600&q=80',
                    'pax'         => match (true) {
                        str_contains($room->room_type, 'Solo')     => '1-2 pax',
                        str_contains($room->room_type, 'Bedspace') => '1 pax',
                        str_contains($room->room_type, 'Barkada')  => '4-6 pax',
                        default                                    => $room->total_beds . ' pax',
                    },
                    'beds'        => $room->total_beds . ' bed' . ($room->total_beds === 1 ? '' : 's'),
                    'bath'        => '1 bath',
                    'floor'       => $room->floor,
                    'desc'        => $room->description ?? 'Cozy room with modern finishes.',
                    'estDate'     => null,
                    'estApprox'   => null,
                    'tourId'      => $room->virtualTour?->id,
                ];
            });

        // Map for React
        $myReservationsForReact = $myReservations->mapWithKeys(function ($r) {
            return [
                $r->reference_no => [
                    'id'      => $r->id,
                    'key'     => $r->reference_no,
                    'status'  => $r->status,
                    'room'    => $r->desired_room,
                    'deposit' => $r->formatted_fee,
                ],
            ];
        })->toArray();

        return view('tenants.reservations', [
            'tenant'         => $tenant,
            'myReservations' => $reservationCards,
            'reservations'   => $myReservationsForReact,
            'rooms'          => $rooms,
        ]);
    }

    /**
     * Store a new reservation (tenant-initiated).
     *
     * NOTE: A user does NOT need an existing `tenants` row to submit a
     * reservation. The Tenant record is created by the admin when the
     * reservation is approved — see Admin\ReservationController::approve().
     *
     * The reservation is linked via `user_id`, not `tenant_id`.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'room_id'         => ['required', 'exists:rooms,id'],
            'check_in_date'   => ['required', 'date'],
            'check_in_time'   => ['nullable', 'string', 'max:20'],
            'check_out_date'  => ['required', 'date', 'after:check_in_date'],
            'check_out_time'  => ['nullable', 'string', 'max:20'],
            'has_companion'   => ['nullable', 'boolean'],
            'notes'           => ['nullable', 'string'],
            'payment_option'  => ['required', 'in:half,full'],
            'reference_no'    => ['required', 'string', 'max:100'],
            'receipt'         => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
        ]);

        $room = Room::findOrFail($validated['room_id']);

        if ($room->status !== 'Available') {
            return response()->json([
                'message' => 'This room is no longer available.',
            ], 422);
        }

        // ---------- Compute pricing ----------
        $in   = Carbon::parse($validated['check_in_date']);
        $out  = Carbon::parse($validated['check_out_date']);
        $days = max(1, $in->diffInDays($out));

        $roomRate     = (float) $room->daily_rent;
        $companionFee = ($validated['has_companion'] ?? false) ? 150 : 0;
        $roomSubtotal = $roomRate * $days;
        $totalCharges = $roomSubtotal + $companionFee;

        $payNow    = $validated['payment_option'] === 'half'
            ? $totalCharges / 2
            : $totalCharges;
        $remaining = $totalCharges - $payNow;

        // ---------- Save uploaded receipt ----------
        $receiptPath = $request->file('receipt')->store('reservations/' . $user->id, 'public');

        // ---------- Create the reservation ----------
        $avatar = Reservation::makeAvatar($user->name);

        $reservation = Reservation::create([
            'reference_no'       => Reservation::generateReferenceNo(),
            'reservation_number' => Reservation::generateReservationNumber(),
            'user_id'            => $user->id,
            'room_id'            => $room->id,
            'tenant_name'        => $user->name,
            'tenant_initial'     => $avatar['initial'],
            'avatar_bg'          => $avatar['bg'],
            'avatar_color'       => $avatar['color'],
            'email'              => $user->email,
            'phone'              => $user->phone,
            'desired_room'       => $room->room_number,
            'room_bed_id'        => $room->room_bed_id,
            'check_in'           => $validated['check_in_date'],
            'check_in_time'      => $validated['check_in_time'] ?? null,
            'check_out'          => $validated['check_out_date'],
            'check_out_time'     => $validated['check_out_time'] ?? null,
            'has_companion'      => (bool) ($validated['has_companion'] ?? false),
            'total_charges'      => $totalCharges,
            'reservation_fee'    => $payNow,
            'payment_option'     => $validated['payment_option'] === 'half' ? 'Down Payment' : 'Full Payment',
            'payment_method'     => 'GCash',
            'remaining'          => $remaining,
            'request_details'    => $validated['notes'] ?? null,
            'proof_of_payment'   => $receiptPath,
            'status'             => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reservation submitted. Awaiting admin approval.',
            'data'    => [
                'reference_no' => $reservation->reference_no,
            ],
        ]);
    }

    /**
     * Cancel a pending reservation (tenant can only cancel their own).
     */
    public function cancel(Reservation $reservation)
    {
        $user = Auth::user();

        if ($reservation->user_id !== $user->id) {
            abort(403, 'You can only cancel your own reservations.');
        }

        if ($reservation->status !== 'Pending') {
            return response()->json([
                'message' => 'Only pending reservations can be cancelled.',
            ], 422);
        }

        // Clean up uploaded proof
        if ($reservation->proof_of_payment) {
            Storage::disk('public')->delete($reservation->proof_of_payment);
        }

        $reservation->update([
            'status' => 'Rejected',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reservation cancelled.',
        ]);
    }
}