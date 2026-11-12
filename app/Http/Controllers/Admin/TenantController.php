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
            ->whereNull('removed_at')
            ->orderByDesc('created_at')
            ->get();

        $metrics = [
            'total'    => Tenant::whereNull('removed_at')->count(),
            'active'   => Tenant::whereNull('removed_at')->where('status', 'active')->count(),
            'inactive' => Tenant::whereNull('removed_at')->where('status', 'inactive')->count(),
        ];

        return view('admin.tenants', compact('tenants', 'metrics'));
    }

    /**
     * Remove a tenant from the Tenants list.
     *
     * The row itself is kept: room changes, stay extensions and message
     * conversations reference it with ON DELETE CASCADE, so a hard delete
     * would wipe that history (the confirmation dialog promises it's kept).
     * The tenant is unassigned from their room, set Inactive and hidden.
     * A newly approved reservation makes them Active and visible again.
     */
    public function destroy(Tenant $tenant)
    {
        $tenant->loadMissing('user', 'room');
        $name = $tenant->user?->name ?? 'this tenant';

        // ---- Guard: never remove someone with something still ongoing ----
        $openReservation = \App\Models\Reservation::where('user_id', $tenant->user_id)
            ->whereIn('status', ['Pending', 'Approved'])
            ->get()
            ->first(fn ($r) => $r->status === 'Pending' || ! $r->isStayOver());

        if ($openReservation) {
            $what = $openReservation->status === 'Pending'
                ? 'a pending reservation'
                : ($openReservation->hasStarted() ? 'an ongoing stay' : 'an upcoming stay');

            return response()->json([
                'message' => "{$name} can't be removed while they have {$what} ({$openReservation->reference_no}). "
                    . 'Reject the reservation or wait until the stay ends.',
            ], 422);
        }

        $closedRefs = \App\Models\Reservation::where('user_id', $tenant->user_id)
            ->whereIn('status', ['Cancelled', 'Rejected'])
            ->pluck('reference_no')->filter()->all();

        $unpaid = \App\Models\Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['Unpaid', 'Partial', 'Pending', 'Overdue'])
            ->where('balance', '>', 0.005)
            ->get()
            ->reject(function ($inv) use ($closedRefs) {
                foreach ($closedRefs as $ref) {
                    if (preg_match('/' . preg_quote($ref, '/') . '(?!\d)/', (string) $inv->description)) return true;
                }
                return false;
            });

        if ($unpaid->isNotEmpty()) {
            return response()->json([
                'message' => "{$name} can't be removed while they have an unsettled balance of ₱"
                    . number_format((float) $unpaid->sum('balance'), 2) . '. Settle or void the invoice first.',
            ], 422);
        }

        // ---- Remove from the list, keep the history ----
        $roomId = $tenant->room_id;

        DB::transaction(function () use ($tenant) {
            $tenant->update([
                'room_id'    => null,
                'status'     => 'inactive',
                'removed_at' => now(),
            ]);
        });

        if ($roomId) {
            \App\Models\Room::find($roomId)?->syncStatus();
        }

        \App\Services\AuditLogger::log([
            'action'       => 'DELETE',
            'module'       => 'TENANTS',
            'description'  => "Removed tenant {$name} from the tenant list"
                . ($tenant->room ? " (unassigned from Room {$tenant->room->room_number})" : '')
                . '. Account and history kept.',
            'status'       => 'warning',
            'subject_type' => Tenant::class,
            'subject_id'   => $tenant->id,
        ]);

        try {
            event(new \App\Events\ReservationUpdated('room_updated', null));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'success' => true,
            'message' => "{$name} was removed from the tenant list.",
        ]);
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

            // Push the change to the tenant's dashboard / services in real time
            try {
                event(new \App\Events\ReservationUpdated('tenant_updated', $tenant->approved_reservation_id));
            } catch (\Throwable $e) {
                report($e);
            }
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