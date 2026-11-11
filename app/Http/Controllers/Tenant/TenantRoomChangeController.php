<?php
// app/Http/Controllers/Tenant/TenantRoomChangeController.php

namespace App\Http\Controllers\Tenant;

use App\Events\ServiceRequestUpdated;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomChangeRequest;
use App\Models\Tenant;
use App\Models\Reservation;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TenantRoomChangeController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tenant = Tenant::with(['room', 'approvedReservation'])->where('user_id', $user->id)->first();

        // ── APPROVED RESERVATION CHECK ──
        $approvedReservation = $tenant
            ? Reservation::where('user_id', $user->id)
            ->where('status', 'Approved')
            ->when($tenant->room_id, fn($q) => $q->where('room_id', $tenant->room_id))
            ->latest()
            ->first()
            : null;

        $hasApprovedReservation = (bool) $approvedReservation;
        $canRequestChange       = $hasApprovedReservation && (bool) $tenant?->room;

        $currentRoom = $tenant?->room?->room_bed_id
            ?? $tenant?->room?->room_number
            ?? '—';

        $requests = RoomChangeRequest::with(['currentRoom', 'requestedRoom'])
            ->where('tenant_id', $tenant?->id)
            ->latest()
            ->get();

        // FIX: Pull the check-out date from the reservation, not the tenant table
        $tenantCheckOut = $tenant?->approvedReservation?->check_out
            ? \Carbon\Carbon::parse($tenant->approvedReservation->check_out)->toDateString()
            : now()->addDays(30)->toDateString();

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
        })->map(function ($room) use ($tenant, $tenantCheckOut) {
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

                // ── BILLING DATA ──
                'daily_rent'       => (float) $room->daily_rent,
                'tenant_check_out' => $tenantCheckOut,
            ];
        })->values();

        $requestsForReact = $requests->mapWithKeys(function ($r) {
            // Fetch the associated invoice to get payment info
            $invoice = Invoice::where('description', 'like', '%' . $r->reference_no . '%')->first();

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
                    // NEW FIELDS for viewing receipt:
                    'payment_reference' => $invoice?->receipt_no,
                    'payment_amount'    => $invoice?->total_amount ?? 0,
                    'payment_proof_url' => $invoice?->receipt_path 
                        ? asset('storage/' . $invoice->receipt_path) 
                        : null,
                ],
            ];
        })->toArray();

        return view('tenants.services.room-change', compact(
            'tenant',
            'currentRoom',
            'requests',
            'requestsForReact',
            'availableRooms',
            'hasApprovedReservation',
            'canRequestChange'
        ));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tenant = Tenant::with('room')->where('user_id', $user->id)->first();

        if (!$tenant || !$tenant->room) {
            return response()->json([
                'message' => 'You are not yet assigned to a room. Please contact the admin.',
            ], 422);
        }

        $hasApprovedReservation = Reservation::where('user_id', $user->id)
            ->where('status', 'Approved')
            ->where('room_id', $tenant->room_id)
            ->exists();

        if (!$hasApprovedReservation) {
            return response()->json([
                'message' => 'A room change can only be requested for an approved reservation.',
            ], 422);
        }

        // 1. Validate the incoming request (now including receipt and reference_no)
        $validated = $request->validate([
            'requested_room' => ['required', 'string', 'max:50'],
            'room_bed_id'    => ['nullable', 'string', 'max:50'],
            'reason'         => ['required', 'string'],
            'reference_no'   => ['required', 'string', 'max:100'],
            'receipt'        => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
        ]);

        $requestedRoomId = null;
        $availability    = 'AVAILABLE';
        $newRoom         = Room::find($validated['requested_room']);

        if ($newRoom) {
            $requestedRoomId = $newRoom->id;
            $availability = match ($newRoom->status) {
                'Occupied' => 'OCCUPIED',
                'Reserved' => 'RESERVED',
                default    => 'AVAILABLE',
            };
        }

        $currentRoomLabel = $tenant->room->room_bed_id ?? $tenant->room->room_number ?? '—';
        $requestedRoomLabel = $newRoom ? 'Room ' . $newRoom->room_number : $validated['requested_room'];

        if (!empty($validated['room_bed_id'])) {
            $requestedRoomLabel .= ' (' . $validated['room_bed_id'] . ')';
        }

        // 2. Save the uploaded receipt
        $receiptPath = $request->file('receipt')->store('reservations/' . $user->id, 'public');

        // 3. Calculate the Bill (Nights x Rate)
        $ratePerDay = (float) ($newRoom?->daily_rent ?? 0);
        $startDate = now()->startOfDay();
        $endDate   = $tenant->approvedReservation?->check_out
            ? \Carbon\Carbon::parse($tenant->approvedReservation->check_out)->startOfDay()
            : now()->addDays(30)->startOfDay();

        $nights = max(1, (int) $startDate->diffInDays($endDate));
        $totalAmount = $ratePerDay * $nights;

        DB::beginTransaction();
        try {
            // 4. Create the Room Change Request
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

                        // 5. Create the Pending Invoice for Admin Verification
            Invoice::create([
                'transaction_no' => Invoice::generateTransactionNo(),
                'tenant_id'      => $tenant->id,
                'room_id'        => $requestedRoomId,
                'tenant_name'    => $user->name,
                'room_number'    => $newRoom?->room_number ?? 'N/A',
                // Ensure #RC- is in the description
                'description'    => 'Room Change Fee — Moved to Room ' . ($newRoom?->room_number ?? 'N/A')
                    . ' (' . $nights . ' night' . ($nights === 1 ? '' : 's') . ', Ref: #RC-' . $roomChange->id . ')',
                'check_in'       => $startDate,
                'check_out'      => $endDate,
                'rate_per_day'   => $ratePerDay,
                'days'           => $nights,
                'total_amount'   => $totalAmount,
                
                'amount_paid'    => $totalAmount, // <-- RECORD WHAT THEY PAID
                'balance'        => 0,            // <-- SET BALANCE TO 0
                
                'due_date'       => now()->toDateString(),
                'payment_method' => 'GCash',
                'status'         => 'Pending',    // <-- STILL PENDING FOR ADMIN VERIFICATION
                'receipt_no'     => $validated['reference_no'],
                'receipt_path'   => $receiptPath,
                'date_paid'      => now()->toDateString(),
            ]);

            DB::commit();

            event(new ServiceRequestUpdated('room_change', 'created', $roomChange->id));

            return response()->json([
                'success' => true,
                'message' => 'Room change request submitted successfully.',
                'data'    => $roomChange,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            if (Storage::disk('public')->exists($receiptPath)) {
                Storage::disk('public')->delete($receiptPath);
            }
            report($e);
            return response()->json([
                'message' => 'Failed to submit request: ' . $e->getMessage(),
            ], 500);
        }
    }

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
            $invoice = Invoice::where('description', 'like', '%' . $r->reference_no . '%')->first();

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
                    'payment_reference' => $invoice?->receipt_no,
                    'payment_amount'    => $invoice?->total_amount ?? 0,
                    'payment_proof_url' => $invoice?->receipt_path 
                        ? asset('storage/' . $invoice->receipt_path) 
                        : null,
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

        $hasApprovedReservation = $tenant
            ? Reservation::where('user_id', $user->id)
            ->where('status', 'Approved')
            ->when($tenant->room_id, fn($q) => $q->where('room_id', $tenant->room_id))
            ->exists()
            : false;

        return response()->json([
            'rows' => $rows,
            'react' => $requestsForReact,
            'has_tenant' => (bool) $tenant && (bool) $tenant->room,
            'has_approved_reservation' => $hasApprovedReservation,
        ]);
    }
}