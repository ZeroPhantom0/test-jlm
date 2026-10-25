<?php
// app/Http/Controllers/Tenant/TenantStayExtensionController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\StayExtensionRequest;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantStayExtensionController extends Controller
{
    /**
     * Display the logged-in tenant's stay extension requests.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Tenant record + current room + approved reservation
        $tenant = Tenant::with(['room', 'approvedReservation'])
            ->where('user_id', $user->id)
            ->first();

        // ── CURRENT LEASE END comes from the approved reservation's check_out ──
        $currentCheckOut = $tenant?->approvedReservation?->check_out;

        $currentLeaseEnd = $currentCheckOut
            ? $currentCheckOut->format('M j, Y')
            : 'Open-ended';

        // The earliest date the tenant is allowed to pick for the new end date.
        // Must be strictly AFTER the current checkout.
        $minRequestedDate = $currentCheckOut
            ? $currentCheckOut->copy()->addDay()->toDateString()
            : now()->toDateString();

        // Only this tenant's stay extension requests
        $requests = StayExtensionRequest::with('room')
            ->where('tenant_id', $tenant?->id)
            ->latest()
            ->get();

        // Room number label
        $roomNumber = $tenant?->room?->room_number ?? '—';

        // Pass a normalised map to the React modals
        $requestsForReact = $requests->mapWithKeys(function ($r) {
            return [
                $r->id => [
                    'id'                  => $r->id,
                    'ref'                 => $r->reference_no,
                    'tenant'              => $r->tenant->user->name ?? 'Unknown',
                    'room'                => $r->room?->room_number
                                                ? 'Room ' . $r->room->room_number
                                                : 'N/A',
                    'current_lease_end'   => $r->current_lease_end?->format('M j, Y') ?? '—',
                    'requested_lease_end' => $r->requested_lease_end?->format('M j, Y') ?? '—',
                    'submitted'           => $r->created_at?->format('M j, Y') ?? '—',
                    'reason'              => $r->reason,
                    'status'              => $r->status,
                ],
            ];
        })->toArray();

        return view('tenants.services.stay-extension', compact(
            'tenant',
            'roomNumber',
            'currentLeaseEnd',
            'minRequestedDate',
            'requests',
            'requestsForReact'
        ));
    }

    /**
     * Store a new stay extension request for the logged-in tenant.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tenant = Tenant::with(['room', 'approvedReservation'])
            ->where('user_id', $user->id)
            ->first();

        if (!$tenant) {
            return response()->json([
                'message' => 'You are not yet assigned to a room. Please contact the admin.',
            ], 422);
        }

        // Server-side guard: requested date must be AFTER the current checkout
        $currentCheckOut = $tenant->approvedReservation?->check_out;

        if (!$currentCheckOut) {
            return response()->json([
                'message' => 'You have no active lease to extend. Please contact the admin.',
            ], 422);
        }

        $validated = $request->validate([
            'current_lease_end'   => ['required', 'date'],
            'requested_lease_end' => [
                'required',
                'date',
                'after:' . $currentCheckOut->toDateString(),
            ],
            'reason'              => ['required', 'string'],
        ], [
            'requested_lease_end.after' =>
                'The requested lease end must be after your current check-out date ('
                . $currentCheckOut->format('M j, Y') . ').',
        ]);

        // Recompute extension days server-side — never trust the client
        $extensionDays = StayExtensionRequest::calculateExtensionDays(
            $currentCheckOut->toDateString(),
            $validated['requested_lease_end']
        );

        if ($extensionDays <= 0) {
            return response()->json([
                'message' => 'The requested lease end must be after your current check-out date.',
            ], 422);
        }

        $stayExtension = StayExtensionRequest::create([
            'reference_no'        => StayExtensionRequest::generateReferenceNo(),
            'tenant_id'           => $tenant->id,
            'room_id'             => $tenant->room_id,
            'current_lease_end'   => $currentCheckOut->toDateString(),
            'requested_lease_end' => $validated['requested_lease_end'],
            'extension_days'      => $extensionDays,
            'reason'              => $validated['reason'],
            'status'              => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stay extension request submitted successfully.',
            'data'    => $stayExtension,
        ]);
    }

    /**
     * Cancel a pending stay extension request.
     */
    public function cancel(StayExtensionRequest $stayExtensionRequest)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $ownsIt = $stayExtensionRequest->tenant?->user_id === $user->id;
        if (!$ownsIt) {
            abort(403, 'You can only cancel your own requests.');
        }

        if ($stayExtensionRequest->status !== 'Pending') {
            return response()->json([
                'message' => 'Only pending requests can be cancelled.',
            ], 422);
        }

        $stayExtensionRequest->update([
            'status' => 'Rejected',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stay extension request cancelled.',
        ]);
    }
}