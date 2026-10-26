<?php
// app/Http/Controllers/Tenant/TenantRoomChangeController.php

namespace App\Http\Controllers\Tenant;

use App\Events\ServiceRequestUpdated;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomChangeRequest;
use App\Models\Tenant;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TenantRoomChangeController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tenant = Tenant::with('room')->where('user_id', $user->id)->first();

        $currentRoom = $tenant?->room?->room_bed_id
            ?? $tenant?->room?->room_number
            ?? '—';

        $requests = RoomChangeRequest::with(['currentRoom', 'requestedRoom'])
            ->where('tenant_id', $tenant?->id)
            ->latest()
            ->get();

        // ── FETCH AVAILABLE ROOMS WITH BED SLOTS ──
        $allRooms = Room::withCount([
            'tenants as occupied_count' => fn($q) => $q->where('status', 'active')
        ])
            ->where('id', '!=', $tenant?->room_id)
            ->orderBy('room_number')
            ->get();

        $availableRooms = $allRooms->filter(function ($room) {
            $emptyBeds = $room->total_beds - $room->occupied_count;
            return $emptyBeds > 0;
        })->map(function ($room) use ($tenant) {
            $emptyBeds = $room->total_beds - $room->occupied_count;

            $bedSlots = [];
            if (in_array($room->room_type, ['Bedspace', 'Barkada Room'])) {
                $heldByTenants = Tenant::where('room_id', $room->id)
                    ->where('status', 'active')
                    ->with('approvedReservation')
                    ->get()
                    ->pluck('approvedReservation.room_bed_id')
                    ->filter()
                    ->all();

                $heldByTenants = array_flip($heldByTenants);

                $heldByReservations = Reservation::where('room_id', $room->id)
                    ->whereIn('status', ['Pending', 'Approved'])
                    ->whereNotNull('room_bed_id')
                    ->where(function ($q) {
                        $q->whereNull('check_out')
                            ->orWhereDate('check_out', '>=', now()->toDateString());
                    })
                    ->pluck('room_bed_id')
                    ->all();
                $heldByReservations = array_flip($heldByReservations);

                $slotIndex = 0;
                for ($i = 1; $i <= ceil($room->total_beds / 2); $i++) {
                    foreach (['U' => 'Upper', 'L' => 'Lower'] as $code => $label) {
                        if ($slotIndex >= $room->total_beds) break 2;

                        $id = "B{$i}-{$code}";
                        $isOccupied = isset($heldByTenants[$id]) || isset($heldByReservations[$id]);

                        $bedSlots[] = [
                            'id'     => $id,
                            'label'  => "Bed {$i} - {$label}",
                            'status' => $isOccupied ? 'Occupied' : 'Available',
                        ];
                        $slotIndex++;
                    }
                }
            }

            return [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'room_type' => $room->room_type,
                'label' => 'Room ' . $room->room_number . ' (' . $room->room_type . ' - ' . $emptyBeds . ' slot' . ($emptyBeds > 1 ? 's' : '') . ' left)',
                'bed_slots' => $bedSlots,
            ];
        })->values();

        $requestsForReact = $requests->mapWithKeys(function ($r) {
            return [
                $r->id => [
                    'id'             => $r->id,
                    'ref'            => $r->reference_no,
                    'current_room'   => $r->current_room,
                    'requested_room' => $r->requested_room,
                    'submitted'      => $r->created_at?->format('M d, Y') ?? '—',
                    'availability'   => ucfirst(strtolower($r->availability ?? 'Available')),
                    'reason'         => $r->reason,
                    'status'         => $r->status,
                ],
            ];
        })->toArray();

        return view('tenants.services.room-change', compact(
            'tenant',
            'currentRoom',
            'requests',
            'requestsForReact',
            'availableRooms'
        ));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tenant = Tenant::with('room')
            ->where('user_id', $user->id)
            ->first();

        if (!$tenant || !$tenant->room) {
            return response()->json([
                'message' => 'You are not yet assigned to a room. Please contact the admin.',
            ], 422);
        }

        $validated = $request->validate([
            'requested_room' => ['required', 'string', 'max:50'],
            'room_bed_id'    => ['nullable', 'string', 'max:50'],
            'reason'         => ['required', 'string'],
        ]);

        $requestedRoomId = null;
        $availability    = 'AVAILABLE';

        $matched = Room::find($validated['requested_room']);

        if ($matched) {
            $requestedRoomId = $matched->id;
            $availability = match ($matched->status) {
                'Occupied' => 'OCCUPIED',
                'Reserved' => 'RESERVED',
                default    => 'AVAILABLE',
            };
        }

        $currentRoomLabel = $tenant->room->room_bed_id ?? $tenant->room->room_number ?? '—';

        $requestedRoomLabel = $matched ? 'Room ' . $matched->room_number : $validated['requested_room'];
        if (!empty($validated['room_bed_id'])) {
            $requestedRoomLabel .= ' (' . $validated['room_bed_id'] . ')';
        }

        $roomChange = RoomChangeRequest::create([
            'reference_no'      => RoomChangeRequest::generateReferenceNo(),
            'tenant_id'         => $tenant->id,
            'current_room_id'   => $tenant->room->id,
            'requested_room_id' => $requestedRoomId,
            'current_room'      => $currentRoomLabel,
            'requested_room'    => $requestedRoomLabel,
            'availability'      => $availability,
            'reason'            => $validated['reason'],
            'status'            => 'Pending',
        ]);

        // Notify admin bell — new request
        event(new ServiceRequestUpdated('room_change', 'created', $roomChange->id));

        return response()->json([
            'success' => true,
            'message' => 'Room change request submitted successfully.',
            'data'    => $roomChange,
        ]);
    }

    /**
     * Cancel a pending room change request.
     */
    public function cancel(RoomChangeRequest $roomChangeRequest)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $ownsIt = $roomChangeRequest->tenant?->user_id === $user->id;
        if (!$ownsIt) {
            abort(403, 'You can only cancel your own requests.');
        }

        if ($roomChangeRequest->status !== 'Pending') {
            return response()->json([
                'message' => 'Only pending requests can be cancelled.',
            ], 422);
        }

        $roomChangeRequest->update([
            'status' => 'Rejected',
            // NOTE: processed_by is NOT set — that's how the admin bell
            // distinguishes "tenant cancelled" from "admin rejected".
        ]);

        // Notify admin bell — tenant cancelled room change
        event(new ServiceRequestUpdated('room_change', 'cancelled', $roomChangeRequest->id));

        return response()->json([
            'success' => true,
            'message' => 'Room change request cancelled.',
        ]);
    }

    public function data()
    {
        $user = Auth::user();
        $tenant = Tenant::with('room')->where('user_id', $user->id)->first();

        $requests = RoomChangeRequest::with(['currentRoom', 'requestedRoom'])
            ->where('tenant_id', $tenant?->id)->latest()->get();

        $requestsForReact = $requests->mapWithKeys(function ($r) {
            return [
                $r->id => [
                    'id' => $r->id,
                    'ref' => $r->reference_no,
                    'current_room' => $r->current_room,
                    'requested_room' => $r->requested_room,
                    'submitted' => $r->created_at?->format('M d, Y') ?? '—',
                    'availability' => ucfirst(strtolower($r->availability ?? 'Available')),
                    'reason' => $r->reason,
                    'status' => $r->status,
                ],
            ];
        })->toArray();

        $rows = $requests->map(function ($r) {
            return [
                'id' => $r->id,
                'current_room' => $r->current_room,
                'requested_room' => $r->requested_room,
                'reason' => $r->reason,
                'date' => $r->created_at?->format('M d, Y'),
                'availability' => ucfirst(strtolower($r->availability ?? 'Available')),
                'is_pending' => $r->status === 'Pending',
            ];
        })->values();

        return response()->json([
            'rows' => $rows,
            'react' => $requestsForReact,
            'has_tenant' => (bool) $tenant && (bool) $tenant->room,
        ]);
    }
}