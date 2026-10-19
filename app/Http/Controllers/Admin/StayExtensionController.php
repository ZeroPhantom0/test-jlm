<?php
// app/Http/Controllers/Admin/StayExtensionController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StayExtensionRequest;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StayExtensionController extends Controller
{
    /**
     * Display the stay extension requests list.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status']);

        $requests = StayExtensionRequest::with(['tenant.user', 'room'])
            ->filter($filters)
            ->latest()
            ->get();

        $metrics = [
            'total'    => StayExtensionRequest::count(),
            'pending'  => StayExtensionRequest::pending()->count(),
            'approved' => StayExtensionRequest::approved()->count(),
            'rejected' => StayExtensionRequest::rejected()->count(),
        ];

        // Pass data for React modals
        $requestsForReact = $requests->mapWithKeys(function ($req) {
            return [$req->id => $req->toReactArray()];
        })->toArray();

        return view('admin.services.stay-extension', compact('requests', 'metrics', 'filters', 'requestsForReact'));
    }

    /**
     * Store a newly created stay extension request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id'           => ['required', 'exists:tenants,id'],
            'room_id'             => ['nullable', 'exists:rooms,id'],
            'current_lease_end'   => ['required', 'date'],
            'requested_lease_end' => ['required', 'date', 'after:current_lease_end'],
            'reason'              => ['required', 'string'],
        ]);

        $extensionDays = StayExtensionRequest::calculateExtensionDays(
            $validated['current_lease_end'],
            $validated['requested_lease_end']
        );

        $stayExtension = StayExtensionRequest::create([
            ...$validated,
            'reference_no'   => StayExtensionRequest::generateReferenceNo(),
            'extension_days' => $extensionDays,
            'status'         => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stay extension request created successfully.',
            'data'    => $stayExtension->load('tenant.user'),
        ]);
    }

    /**
     * Display the specified stay extension request.
     */
    public function show(StayExtensionRequest $stayExtensionRequest)
    {
        $stayExtensionRequest->load(['tenant.user', 'room']);

        return response()->json([
            'success' => true,
            'data'    => $stayExtensionRequest->toReactArray(),
        ]);
    }

    /**
     * Update the specified stay extension request (admin notes, status).
     */
    public function update(Request $request, StayExtensionRequest $stayExtensionRequest)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
            'status'      => ['sometimes', Rule::in(['Pending', 'Approved', 'Rejected'])],
        ]);

        if (isset($validated['status']) && in_array($validated['status'], ['Approved', 'Rejected'])) {
            $validated['processed_by'] = $request->user()->id;
            $validated['processed_at'] = now();
        }

        $stayExtensionRequest->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Stay extension request updated successfully.',
            'data'    => $stayExtensionRequest->fresh()->load('tenant.user'),
        ]);
    }

    /**
     * Approve a stay extension request.
     */
        public function approve(Request $request, StayExtensionRequest $stayExtensionRequest)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
        ]);

        // Extend the SOURCE reservation's check_out — that's the single
        // source of truth for the tenant's move-out date.
        if ($stayExtensionRequest->tenant) {
            $tenant = $stayExtensionRequest->tenant;
            $reservation = $tenant->approvedReservation;

            if ($reservation) {
                $reservation->update([
                    'check_out'      => $stayExtensionRequest->requested_lease_end,
                    'check_out_time' => $reservation->check_out_time,
                ]);
            }
        }

        $stayExtensionRequest->update([
            'status'       => 'Approved',
            'admin_notes'  => $validated['admin_notes'] ?? $stayExtensionRequest->admin_notes,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stay extension request approved.',
            'data'    => $stayExtensionRequest->fresh(),
        ]);
    }

    /**
     * Reject a stay extension request.
     */
    public function reject(Request $request, StayExtensionRequest $stayExtensionRequest)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
        ]);

        $stayExtensionRequest->update([
            'status'       => 'Rejected',
            'admin_notes'  => $validated['admin_notes'] ?? $stayExtensionRequest->admin_notes,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stay extension request rejected.',
            'data'    => $stayExtensionRequest->fresh(),
        ]);
    }

    /**
     * Remove the specified stay extension request.
     */
    public function destroy(StayExtensionRequest $stayExtensionRequest)
    {
        $stayExtensionRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stay extension request deleted successfully.',
        ]);
    }
}