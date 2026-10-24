<?php
// app/Http/Controllers/Admin/TenantController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with([
                'user.personalInformation',
                'user.identityVerification',
                'room',
                'approvedReservation',
            ])
            ->orderByDesc('created_at')
            ->get();

        $metrics = [
            'total'    => Tenant::count(),
            'active'   => Tenant::where('status', 'active')->count(),
            'inactive' => Tenant::where('status', 'inactive')->count(),
        ];

        return view('admin.tenants', compact('tenants', 'metrics'));
    }

    /**
     * Update the tenant (spans users, tenants, and the approved reservation).
     */
    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($tenant->user_id),
            ],
            'phone'         => ['nullable', 'string', 'max:20'],
            'check_in'      => ['nullable', 'date'],
            'check_in_time' => ['nullable', 'string', 'max:20'],
            'check_out'     => ['nullable', 'date'],
            'check_out_time'=> ['nullable', 'string', 'max:20'],
            'rent_rate'     => ['nullable', 'numeric', 'min:0'],
            'status'        => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $checkInTime  = $this->normaliseTime($validated['check_in_time']  ?? null);
        $checkOutTime = $this->normaliseTime($validated['check_out_time'] ?? null);

        // ── 1. Determine if data actually changed ─────────────────────
        $hasChanges = false;

        // Check User table
        if ($tenant->user) {
            if (
                $tenant->user->name !== $validated['name'] ||
                $tenant->user->email !== $validated['email'] ||
                (string) $tenant->user->phone !== (string) ($validated['phone'] ?? null)
            ) {
                $hasChanges = true;
            }
        }

        // Check Tenant table
        if (
            (float) $tenant->rent_rate !== (float) ($validated['rent_rate'] ?? 0) ||
            $tenant->status !== $validated['status']
        ) {
            $hasChanges = true;
        }

        // Check Reservation table (if exists)
        if ($tenant->approvedReservation && !$hasChanges) {
            $res = $tenant->approvedReservation;
            $incomingCheckIn = $validated['check_in'] ?? null;
            $existingCheckIn = $res->check_in ? $res->check_in->format('Y-m-d') : null;
            
            $incomingCheckOut = $validated['check_out'] ?? null;
            $existingCheckOut = $res->check_out ? $res->check_out->format('Y-m-d') : null;

            if (
                $incomingCheckIn !== $existingCheckIn ||
                $checkInTime !== $res->check_in_time ||
                $incomingCheckOut !== $existingCheckOut ||
                $checkOutTime !== $res->check_out_time
            ) {
                $hasChanges = true;
            }
        }

        // ── 2. Save Data ──────────────────────────────────────────────
        DB::transaction(function () use ($tenant, $validated, $checkInTime, $checkOutTime) {
            if ($tenant->user) {
                $tenant->user->update([
                    'name'  => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                ]);
            }

            $tenant->update([
                'rent_rate' => $validated['rent_rate'] ?? 0,
                'status'    => $validated['status'],
            ]);

            if ($tenant->approvedReservation) {
                $tenant->approvedReservation->update([
                    'check_in'       => $validated['check_in']  ?? null,
                    'check_in_time'  => $checkInTime,
                    'check_out'      => $validated['check_out'] ?? null,
                    'check_out_time' => $checkOutTime,
                ]);
            }
        });

        // ── 3. Log Only if Changes Occurred ───────────────────────────
        if ($hasChanges) {
            // Assuming you have an AuditLogger facade/service for Admin actions
            // If not, you can use \App\Services\AuditLogger::log(...)
            \App\Services\AuditLogger::log([
                'action'       => 'UPDATE',
                'module'       => 'TENANTS',
                'description'  => 'Updated tenant details for ' . $tenant->name,
                'subject_type' => Tenant::class,
                'subject_id'   => $tenant->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tenant updated successfully.',
            'data'    => $tenant->fresh()->load([
                'user.personalInformation',
                'user.identityVerification',
                'room',
                'approvedReservation',
            ])->toReactArray(),
        ]);
    }

    private function normaliseTime(?string $time): ?string
    {
        if (!$time) {
            return null;
        }

        $time = trim($time);

        // Try 12h format with AM/PM
        if (preg_match('/^(\d{1,2}):(\d{2})\s*([APap][Mm])$/', $time, $m)) {
            $hours = (int) $m[1];
            $minutes = $m[2];
            $meridiem = strtoupper($m[3]);

            if ($hours < 1 || $hours > 12) {
                return null;
            }
            if ($meridiem === 'PM' && $hours !== 12) {
                $hours += 12;
            } elseif ($meridiem === 'AM' && $hours === 12) {
                $hours = 0;
            }
            return sprintf('%02d:%s:00', $hours, $minutes);
        }

        // Try 24h "H:mm" or "HH:mm" or "HH:mm:ss"
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $time, $m)) {
            $hours = (int) $m[1];
            $minutes = $m[2];
            $seconds = $m[3] ?? '00';
            if ($hours > 23 || (int) $minutes > 59) {
                return null;
            }
            return sprintf('%02d:%s:%s', $hours, $minutes, $seconds);
        }

        return null;
    }
}