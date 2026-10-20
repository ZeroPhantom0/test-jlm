<?php
// app/Http/Controllers/Admin/RoomChangeController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomChangeRequest;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomChangeController extends Controller
{
    /**
     * Display the room change requests list.
     */
    public function index()
    {
        $requests = RoomChangeRequest::with(['tenant.user', 'currentRoom', 'requestedRoom'])
            ->latest()
            ->get();

        $metrics = [
            'total'    => RoomChangeRequest::count(),
            'pending'  => RoomChangeRequest::pending()->count(),
            'approved' => RoomChangeRequest::approved()->count(),
            'rejected' => RoomChangeRequest::rejected()->count(),
        ];

        $requestsForReact = $requests->mapWithKeys(function ($req) {
            return [$req->id => $req->toReactArray()];
        })->toArray();

        return view('admin.services.room-change', compact('requests', 'metrics', 'requestsForReact'));
    }

    /**
     * Store a newly created room change request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id'         => ['required', 'exists:tenants,id'],
            'current_room_id'   => ['nullable', 'exists:rooms,id'],
            'requested_room_id' => ['nullable', 'exists:rooms,id'],
            'current_room'      => ['required', 'string', 'max:50'],
            'requested_room'    => ['required', 'string', 'max:50'],
            'reason'            => ['required', 'string'],
        ]);

        $availability = 'AVAILABLE';
        if (!empty($validated['requested_room_id'])) {
            $room = Room::find($validated['requested_room_id']);
            if ($room) {
                $availability = match ($room->status) {
                    'Occupied' => 'OCCUPIED',
                    'Reserved' => 'RESERVED',
                    default    => 'AVAILABLE',
                };
            }
        }

        $roomChange = RoomChangeRequest::create([
            ...$validated,
            'reference_no' => RoomChangeRequest::generateReferenceNo(),
            'availability' => $availability,
            'status'       => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Room change request created successfully.',
            'data'    => $roomChange->load('tenant.user'),
        ]);
    }

    /**
     * Display the specified room change request.
     */
    public function show(RoomChangeRequest $roomChangeRequest)
    {
        $roomChangeRequest->load(['tenant.user', 'currentRoom', 'requestedRoom']);

        return response()->json([
            'success' => true,
            'data'    => $roomChangeRequest->toReactArray(),
        ]);
    }

    /**
     * Update the specified room change request (admin notes, status).
     */
    public function update(Request $request, RoomChangeRequest $roomChangeRequest)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
            'status'      => ['sometimes', Rule::in(['Pending', 'Approved', 'Rejected'])],
        ]);

        if (isset($validated['status']) && in_array($validated['status'], ['Approved', 'Rejected'])) {
            $validated['processed_by'] = $request->user()->id;
            $validated['processed_at'] = now();
        }

        $roomChangeRequest->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Room change request updated successfully.',
            'data'    => $roomChangeRequest->fresh()->load('tenant.user'),
        ]);
    }

    /**
     * Approve a room change request.
     *
     * - Move the tenant to the new room.
     * - Recompute status for BOTH rooms by real occupancy
     *   (Reserved only when full, otherwise Available).
     */
    public function approve(Request $request, RoomChangeRequest $roomChangeRequest)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
        ]);

        if ($roomChangeRequest->tenant && $roomChangeRequest->requested_room_id) {
            $oldRoom = $roomChangeRequest->currentRoom;
            $newRoom = Room::find($roomChangeRequest->requested_room_id);

            if ($newRoom) {
                $roomChangeRequest->tenant->update(['room_id' => $newRoom->id]);
            }

            // Re-sync BOTH rooms based on actual occupancy.
            if ($oldRoom) {
                $oldRoom->syncStatus();
            }
            if ($newRoom) {
                $newRoom->syncStatus();
            }
        }

        $roomChangeRequest->update([
            'status'       => 'Approved',
            'admin_notes'  => $validated['admin_notes'] ?? $roomChangeRequest->admin_notes,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Room change request approved.',
            'data'    => $roomChangeRequest->fresh(),
        ]);
    }

    /**
     * Reject a room change request.
     */
    public function reject(Request $request, RoomChangeRequest $roomChangeRequest)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
        ]);

        $roomChangeRequest->update([
            'status'       => 'Rejected',
            'admin_notes'  => $validated['admin_notes'] ?? $roomChangeRequest->admin_notes,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Room change request rejected.',
            'data'    => $roomChangeRequest->fresh(),
        ]);
    }

    /**
     * Remove the specified room change request.
     */
    public function destroy(RoomChangeRequest $roomChangeRequest)
    {
        $roomChangeRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Room change request deleted successfully.',
        ]);
    }
}