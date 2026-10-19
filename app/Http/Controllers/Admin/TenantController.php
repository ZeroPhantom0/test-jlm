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
     *
     * Lease dates live on the reservation — the tenant record only holds
     * rent_rate + status. We keep it that way.
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
            'move_in'       => ['nullable', 'date'],
            'move_in_time'  => ['nullable', 'string', 'max:20'],
            'move_out'      => ['nullable', 'date'],
            'move_out_time' => ['nullable', 'string', 'max:20'],
            'rent_rate'     => ['nullable', 'numeric', 'min:0'],
            'status'        => ['required', Rule::in(['active', 'inactive'])],
        ]);

        // Normalise time strings to 24h "H:i:s" (or null)
        $moveInTime  = $this->normaliseTime($validated['move_in_time']  ?? null);
        $moveOutTime = $this->normaliseTime($validated['move_out_time'] ?? null);

        DB::transaction(function () use ($tenant, $validated, $moveInTime, $moveOutTime) {
            // 1. users table
            if ($tenant->user) {
                $tenant->user->update([
                    'name'  => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                ]);
            }

            // 2. tenants table
            $tenant->update([
                'rent_rate' => $validated['rent_rate'] ?? 0,
                'status'    => $validated['status'],
            ]);

            // 3. reservation table (source of truth for lease dates)
            if ($tenant->approvedReservation) {
                $tenant->approvedReservation->update([
                    'check_in'       => $validated['move_in']  ?? null,
                    'check_in_time'  => $moveInTime,
                    'check_out'      => $validated['move_out'] ?? null,
                    'check_out_time' => $moveOutTime,
                ]);
            }
        });

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

    /**
     * Accepts "9:30 AM", "09:30", "9:30", "21:30", "21:30:00" → "HH:mm:ss".
     * Returns null for empty input.
     */
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