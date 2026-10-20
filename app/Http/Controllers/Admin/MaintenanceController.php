<?php
// app/Http/Controllers/Admin/MaintenanceController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    /**
     * Display the maintenance requests list.
     */
    public function index()
    {
        $requests = MaintenanceRequest::with(['tenant.user', 'room'])
            ->latest()
            ->get();

        $metrics = [
            'total'       => MaintenanceRequest::count(),
            'pending'     => MaintenanceRequest::pending()->count(),
            'in_progress' => MaintenanceRequest::inProgress()->count(),
            'completed'   => MaintenanceRequest::completed()->count(),
        ];

        return view('admin.services.maintenance', compact('requests', 'metrics'));
    }

    /**
     * Store a newly created maintenance request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['required', 'string'],
            'category'       => ['required', 'string', 'max:100'],
            'priority'       => ['required', Rule::in(['Low', 'Medium', 'High', 'Critical'])],
            'preferred_date' => ['nullable', 'date'],
            'preferred_time' => ['nullable', 'string', 'max:50'],
            'room_id'        => ['nullable', 'exists:rooms,id'],
            'tenant_id'      => ['nullable', 'exists:tenants,id'],
        ]);

        $request = MaintenanceRequest::create([
            ...$validated,
            'status' => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance request created successfully.',
            'data'    => $request,
        ]);
    }

    /**
     * Display the specified maintenance request.
     */
    public function show(MaintenanceRequest $maintenanceRequest)
    {
        $maintenanceRequest->load(['tenant.user', 'room']);

        return response()->json([
            'success' => true,
            'data'    => $maintenanceRequest,
        ]);
    }

    /**
     * Update the specified maintenance request.
     */
    public function update(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $validated = $request->validate([
            'title'          => ['sometimes', 'string', 'max:255'],
            'description'    => ['sometimes', 'string'],
            'category'       => ['sometimes', 'string', 'max:100'],
            'priority'       => ['sometimes', Rule::in(['Low', 'Medium', 'High', 'Critical'])],
            'status'         => ['sometimes', Rule::in(['Pending', 'In Progress', 'Completed', 'Cancelled'])],
            'assigned_staff' => ['nullable', 'string', 'max:255'],
            'scheduled_date' => ['nullable', 'date'],
            'preferred_date' => ['nullable', 'date'],
            'preferred_time' => ['nullable', 'string', 'max:50'],
        ]);

        // Auto-set completed_at when status becomes Completed
        if (isset($validated['status']) && $validated['status'] === 'Completed') {
            $validated['completed_at'] = now();
        }

        $maintenanceRequest->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance request updated successfully.',
            'data'    => $maintenanceRequest->fresh(),
        ]);
    }

    /**
     * Remove the specified maintenance request.
     */
    public function destroy(MaintenanceRequest $maintenanceRequest)
    {
        $maintenanceRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance request deleted successfully.',
        ]);
    }
}