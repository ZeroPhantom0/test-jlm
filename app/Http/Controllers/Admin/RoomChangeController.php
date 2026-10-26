<?php
// app/Http/Controllers/Admin/RoomChangeController.php

namespace App\Http\Controllers\Admin;

use App\Events\InvoiceUpdated;
use App\Events\ReservationUpdated;
use App\Events\ServiceRequestUpdated;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\RoomChangeRequest;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoomChangeController extends Controller
{
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

        event(new ServiceRequestUpdated('room_change', 'created', $roomChange->id));

        return response()->json([
            'success' => true,
            'message' => 'Room change request created successfully.',
            'data'    => $roomChange->load('tenant.user'),
        ]);
    }

    public function show(RoomChangeRequest $roomChangeRequest)
    {
        $roomChangeRequest->load(['tenant.user', 'currentRoom', 'requestedRoom']);

        return response()->json([
            'success' => true,
            'data'    => $roomChangeRequest->toReactArray(),
        ]);
    }

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

        event(new ServiceRequestUpdated('room_change', 'updated', $roomChangeRequest->id));

        return response()->json([
            'success' => true,
            'message' => 'Room change request updated successfully.',
            'data'    => $roomChangeRequest->fresh()->load('tenant.user'),
        ]);
    }

    public function approve(Request $request, RoomChangeRequest $roomChangeRequest)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
        ]);

        if ($roomChangeRequest->tenant && $roomChangeRequest->requested_room_id) {
            $oldRoom = $roomChangeRequest->currentRoom;
            $newRoom = Room::find($roomChangeRequest->requested_room_id);

            DB::beginTransaction();
            try {
                $invoiceCreated = false;

                if ($newRoom) {
                    $roomChangeRequest->tenant->update(['room_id' => $newRoom->id]);

                    if ($oldRoom) {
                        $oldRoom->syncStatus();
                    }
                    $newRoom->syncStatus();

                    $invoiceCreated = $this->createBillingForNewRoom($roomChangeRequest, $newRoom) !== null;
                }

                $roomChangeRequest->update([
                    'status'       => 'Approved',
                    'admin_notes'  => $validated['admin_notes'] ?? $roomChangeRequest->admin_notes,
                    'processed_by' => $request->user()->id,
                    'processed_at' => now(),
                ]);

                DB::commit();

                if ($invoiceCreated) {
                    event(new InvoiceUpdated('created', null));
                }
                event(new ReservationUpdated('updated', null));
                event(new ServiceRequestUpdated('room_change', 'approved', $roomChangeRequest->id));

                return response()->json([
                    'success' => true,
                    'message' => 'Room change request approved. Invoice generated.',
                    'data'    => $roomChangeRequest->fresh(),
                ]);
            } catch (\Throwable $e) {
                DB::rollBack();
                report($e);
                return response()->json([
                    'message' => 'Failed to approve room change: ' . $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Invalid room change request. Tenant or room missing.',
        ], 422);
    }

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

        event(new ServiceRequestUpdated('room_change', 'rejected', $roomChangeRequest->id));

        return response()->json([
            'success' => true,
            'message' => 'Room change request rejected.',
            'data'    => $roomChangeRequest->fresh(),
        ]);
    }

    public function data()
    {
        $requests = RoomChangeRequest::with(['tenant.user', 'currentRoom', 'requestedRoom'])->latest()->get();

        $metrics = [
            'total'    => RoomChangeRequest::count(),
            'pending'  => RoomChangeRequest::pending()->count(),
            'approved' => RoomChangeRequest::approved()->count(),
            'rejected' => RoomChangeRequest::rejected()->count(),
        ];

        $rows = $requests->map(function ($req) {
            return [
                'id'                 => $req->id,
                'tenant_name'        => $req->tenant->user->name ?? 'Unknown',
                'tenant_email'       => $req->tenant->user->email ?? '',
                'reference_no'       => $req->reference_no,
                'current_room'       => $req->current_room,
                'requested_room'     => $req->requested_room,
                'availability'       => $req->availability,
                'availability_class' => $req->availability_class,
                'reason'             => $req->reason,
                'status'             => $req->status,
                'status_class'       => $req->status_class,
                'status_icon'        => $req->status_icon,
                'date'               => $req->created_at?->format('M d, Y'),
                'is_pending'         => $req->status === 'Pending',
            ];
        })->values();

        return response()->json(['rows' => $rows, 'metrics' => $metrics]);
    }

    public function destroy(RoomChangeRequest $roomChangeRequest)
    {
        $id = $roomChangeRequest->id;
        $roomChangeRequest->delete();

        event(new ServiceRequestUpdated('room_change', 'deleted', $id));

        return response()->json([
            'success' => true,
            'message' => 'Room change request deleted successfully.',
        ]);
    }

    // ============================================================
    // HELPERS
    // ============================================================

    private function createBillingForNewRoom(RoomChangeRequest $request, Room $newRoom): ?Invoice
    {
        $tenant = $request->tenant;

        if (!$tenant) {
            return null;
        }

        $ratePerDay  = (float) $newRoom->daily_rent;
        $days        = 1;
        $totalAmount = $ratePerDay * $days;

        return Invoice::create([
            'transaction_no' => Invoice::generateTransactionNo(),
            'tenant_id'      => $tenant->id,
            'room_id'        => $newRoom->id,
            'tenant_name'    => $tenant->user->name ?? 'Unknown',
            'room_number'    => $newRoom->room_number,
            'description'    => 'Room Change Fee — Moved to Room ' . $newRoom->room_number . ' (Ref: ' . $request->reference_no . ')',
            'check_in'       => now(),
            'check_out'      => now()->addDays($days),
            'rate_per_day'   => $ratePerDay,
            'days'           => $days,
            'total_amount'   => $totalAmount,
            'amount_paid'    => 0,
            'balance'        => $totalAmount,
            'due_date'       => now()->addDays(7)->toDateString(),
            'payment_method' => 'Not Set',
            'status'         => 'Unpaid',
        ]);
    }
}