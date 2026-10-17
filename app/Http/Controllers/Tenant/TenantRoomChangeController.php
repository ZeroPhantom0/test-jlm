<?php
// app/Http/Controllers/Tenant/TenantRoomChangeController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomChangeRequest;
use App\Models\Tenant;
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
        $tenant = Tenant::with('room')
            ->where('user_id', $user->id)
            ->first();

        // Current room identifier (e.g. "R101-B1-U" or "101")
        $currentRoom = $tenant?->room?->room_bed_id
            ?? $tenant?->room?->room_number
            ?? '—';

        // Only this tenant's room change requests
        $requests = RoomChangeRequest::with(['currentRoom', 'requestedRoom'])
            ->where('tenant_id', $tenant?->id)
            ->latest()
            ->get();

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
            'requestsForReact'
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
            'reason'         => ['required', 'string'],
        ]);

        // Try to resolve the requested room to compute availability
        $requestedRoomId = null;
        $availability    = 'AVAILABLE';

        // Match by room_number OR room_bed_id
        $matched = Room::where('room_number', $validated['requested_room'])
            ->orWhere('room_bed_id', $validated['requested_room'])
            ->first();

        if ($matched) {
            $requestedRoomId = $matched->id;
            $availability = match ($matched->status) {
                'Occupied' => 'OCCUPIED',
                'Reserved' => 'RESERVED',
                default    => 'AVAILABLE',
            };
        }

        $currentRoomLabel = $tenant->room->room_bed_id ?? $tenant->room->room_number ?? '—';

        $roomChange = RoomChangeRequest::create([
            'reference_no'      => RoomChangeRequest::generateReferenceNo(),
            'tenant_id'         => $tenant->id,
            'current_room_id'   => $tenant->room->id,
            'requested_room_id' => $requestedRoomId,
            'current_room'      => $currentRoomLabel,
            'requested_room'    => $validated['requested_room'],
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
            'status' => 'Rejected', // or a dedicated 'Cancelled' if your enum supports it
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Room change request cancelled.',
        ]);
    }
}