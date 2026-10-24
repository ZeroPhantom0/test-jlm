<?php
// app/Http/Controllers/Tenant/TenantRoomChangeController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomChangeRequest;
use App\Models\Tenant;
use App\Models\Reservation; // <-- ADDED IMPORT to fix the Intelephense error
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TenantRoomChangeController extends Controller
{
    /**
     * Display the logged-in tenant's room change requests.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Tenant record for the logged-in user
        $tenant = Tenant::with('room')->where('user_id', $user->id)->first();

        // Current room identifier
        $currentRoom = $tenant?->room?->room_bed_id
            ?? $tenant?->room?->room_number
            ?? '—';

        // Only this tenant's room change requests
        $requests = RoomChangeRequest::with(['currentRoom', 'requestedRoom'])
            ->where('tenant_id', $tenant?->id)
            ->latest()
            ->get();

        // ── FETCH AVAILABLE ROOMS WITH BED SLOTS ──
        $allRooms = Room::withCount([
                'tenants as occupied_count' => fn ($q) => $q->where('status', 'active')
            ])
            ->where('id', '!=', $tenant?->room_id)
            ->orderBy('room_number')
            ->get();

        $availableRooms = $allRooms->filter(function ($room) {
            $emptyBeds = $room->total_beds - $room->occupied_count;
            return $emptyBeds > 0;
        })->map(function ($room) use ($tenant) {
            $emptyBeds = $room->total_beds - $room->occupied_count;
            
            // Calculate bed slots for Bedspace/Barkada rooms
            $bedSlots = [];
            if (in_array($room->room_type, ['Bedspace', 'Barkada Room'])) {
                // 1. Beds held by currently active tenants in this room
                $heldByTenants = Tenant::where('room_id', $room->id)
                    ->where('status', 'active')
                    ->with('approvedReservation')
                    ->get()
                    ->pluck('approvedReservation.room_bed_id')
                    ->filter()
                    ->all();
                
                $heldByTenants = array_flip($heldByTenants);

                // 2. Beds held by approved/pending reservations for this room
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

                // 3. Build the slot array
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
                'bed_slots' => $bedSlots, // <-- Pass the slots
            ];
        })->values();

        // Pass a normalised map to the React modals
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

    /**
     * Store a new room change request for the logged-in tenant.
     */
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
            'room_bed_id'    => ['nullable', 'string', 'max:50'], // <-- ADDED
            'reason'         => ['required', 'string'],
        ]);

        // Resolve the requested room using the ID we passed from the frontend
        $requestedRoomId = null;
        $availability    = 'AVAILABLE';

        // Since we changed the dropdown to pass the ID, we find by ID
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
        
        // Build the requested room label so the Admin sees the bed choice
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
            'requested_room'    => $requestedRoomLabel, // Save with bed info
            'availability'      => $availability,
            'reason'            => $validated['reason'],
            'status'            => 'Pending',
        ]);

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
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Room change request cancelled.',
        ]);
    }
}