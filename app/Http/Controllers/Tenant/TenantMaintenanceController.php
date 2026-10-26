<?php
// app/Http/Controllers/Tenant/TenantMaintenanceController.php

namespace App\Http\Controllers\Tenant;

use App\Events\MaintenanceUpdated;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TenantMaintenanceController extends Controller
{
    /**
     * Display the logged-in tenant's maintenance requests.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tenant = Tenant::with('room')
            ->where('user_id', $user->id)
            ->first();

        $requests = MaintenanceRequest::with('room')
            ->where('tenant_id', $tenant?->id)
            ->latest()
            ->get();

        $requestsForReact = $requests->mapWithKeys(function ($r) {
            return [
                $r->id => [
                    'id'         => $r->id,
                    'ref'        => 'sr-' . $r->id,
                    'title'      => $r->title,
                    'desc'       => $r->description,
                    'category'   => $r->category,
                    'priority'   => strtoupper($r->priority ?? 'MEDIUM'),
                    'submitted'  => $r->created_at?->format('M d, Y') ?? '—',
                    'staff'      => $r->assigned_staff,
                    'scheduled'  => $r->scheduled_date?->format('M d, Y'),
                    'unit'       => $r->room?->room_number
                        ? 'Room ' . $r->room->room_number
                        : 'N/A',
                    'status'     => $r->status,
                ],
            ];
        })->toArray();

        return view('tenants.services.maintenance', compact(
            'tenant',
            'requests',
            'requestsForReact'
        ));
    }

    /**
     * Store a new maintenance request for the logged-in tenant.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tenant = Tenant::with('room')
            ->where('user_id', $user->id)
            ->first();

        if (!$tenant) {
            return response()->json([
                'message' => 'You are not yet assigned to a room. Please contact the admin.',
            ], 422);
        }

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category'    => ['required', 'string', 'max:100'],
            'priority'    => ['required', Rule::in(['Low', 'Medium', 'High', 'Critical'])],
        ]);

        $maintenanceRequest = MaintenanceRequest::create([
            ...$validated,
            'tenant_id' => $tenant->id,
            'room_id'   => $tenant->room_id,
            'status'    => 'Pending',
        ]);

        // Notify admin bell + refresh admin page
        event(new MaintenanceUpdated('created', $maintenanceRequest->id));

        return response()->json([
            'success' => true,
            'message' => 'Maintenance request submitted successfully.',
            'data'    => $maintenanceRequest,
        ]);
    }

    /**
     * Cancel a pending maintenance request (tenant can only cancel their own).
     */
    public function cancel(MaintenanceRequest $maintenanceRequest)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ownership check
        $ownsIt = $maintenanceRequest->tenant?->user_id === $user->id;
        if (!$ownsIt) {
            abort(403, 'You can only cancel your own requests.');
        }

        if ($maintenanceRequest->status !== 'Pending') {
            return response()->json([
                'message' => 'Only pending requests can be cancelled.',
            ], 422);
        }

        $maintenanceRequest->update([
            'status' => 'Cancelled',
        ]);

        // Notify admin bell — admin sees "tenant cancelled maintenance request"
        event(new MaintenanceUpdated('cancelled', $maintenanceRequest->id));

        return response()->json([
            'success' => true,
            'message' => 'Maintenance request cancelled.',
        ]);
    }

    public function data()
    {
        $user = Auth::user();
        $tenant = Tenant::with('room')->where('user_id', $user->id)->first();

        $requests = MaintenanceRequest::with('room')->where('tenant_id', $tenant?->id)->latest()->get();

        $requestsForReact = $requests->mapWithKeys(function ($r) {
            return [
                $r->id => [
                    'id' => $r->id,
                    'ref' => 'sr-' . $r->id,
                    'title' => $r->title,
                    'desc' => $r->description,
                    'category' => $r->category,
                    'priority' => strtoupper($r->priority ?? 'MEDIUM'),
                    'submitted' => $r->created_at?->format('M d, Y') ?? '—',
                    'staff' => $r->assigned_staff,
                    'scheduled' => $r->scheduled_date?->format('M d, Y'),
                    'unit' => $r->room?->room_number ? 'Room ' . $r->room->room_number : 'N/A',
                    'status' => $r->status,
                ],
            ];
        })->toArray();

        $rows = $requests->map(function ($r) {
            $statusClasses = match ($r->status) {
                'Pending' => 'bg-amber-100 text-amber-700',
                'In Progress' => 'bg-blue-100 text-blue-600',
                default => 'bg-slate-100 text-slate-500',
            };
            $statusIcon = match ($r->status) {
                'Pending' => 'fa-regular fa-clock',
                'In Progress' => 'fa-solid fa-spinner',
                default => 'fa-regular fa-circle',
            };
            return [
                'id' => $r->id,
                'title' => $r->title,
                'description' => $r->description,
                'assigned_staff' => $r->assigned_staff,
                'status' => $r->status,
                'status_classes' => $statusClasses,
                'status_icon' => $statusIcon,
                'is_finalized' => in_array($r->status, ['Completed', 'Cancelled']),
                'is_pending' => $r->status === 'Pending',
            ];
        })->values();

        return response()->json([
            'rows' => $rows,
            'react' => $requestsForReact,
            'has_tenant' => (bool) $tenant,
        ]);
    }
}