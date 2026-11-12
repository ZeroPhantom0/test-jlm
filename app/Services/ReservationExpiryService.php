<?php
// app/Services/ReservationExpiryService.php

namespace App\Services;

use App\Events\ReservationUpdated;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Ends stays whose check-out has passed.
 *
 *  1. Approved reservations past their check-out moment (date + time) → Expired.
 *  2. The tenant living on that reservation is released (inactive, no room),
 *     so the room stops showing as their "Current Room" / Occupied.
 *  3. Repair: tenants still marked active on a reservation that already ended
 *     (Expired / Cancelled / Rejected) are released too.
 *
 * Safe to run often — it only touches rows that need changing.
 */
class ReservationExpiryService
{
    private const ENDED = ['Expired', 'Cancelled', 'Rejected'];

    /**
     * @return array{expired: string[], released: int}
     */
    public function run(bool $dryRun = false): array
    {
        // Date pre-filter in SQL; the exact check-out time is checked in PHP.
        $due = Reservation::query()
            ->where('status', 'Approved')
            ->whereNotNull('check_out')
            ->whereDate('check_out', '<=', now()->toDateString())
            ->get()
            ->filter(fn (Reservation $r) => $r->isStayOver())
            ->values();

        // Tenants still holding a room on a stay that already ended
        $stuckTenants = Tenant::query()
            ->whereNotNull('room_id')
            ->whereHas('approvedReservation', fn ($q) => $q->whereIn('status', self::ENDED))
            ->get();

        if ($dryRun) {
            return [
                'expired'  => $due->pluck('reference_no')->all(),
                'released' => $stuckTenants->count() + $due->count(),
            ];
        }

        // Room status follows the clock: a booking turns a room from
        // Reserved → Occupied at its check-in moment, and back to Available
        // at check-out. Re-sync every room (only changed rows are saved).
        try {
            Room::all()->each->syncStatus();
        } catch (\Throwable $e) {
            report($e);
        }

        $roomIds  = [];
        $released = 0;

        DB::transaction(function () use ($due, $stuckTenants, &$roomIds, &$released) {
            foreach ($due as $r) {
                $r->update(['status' => 'Expired']);
                if ($r->room_id) $roomIds[$r->room_id] = true;

                $released += $this->releaseTenantsOf($r->id, $roomIds);
            }

            foreach ($stuckTenants as $tenant) {
                if ($tenant->room_id) $roomIds[$tenant->room_id] = true;
                $tenant->update(['room_id' => null]); // free the room; Active/Inactive follows the 6-month rule
                $released++;
            }

            foreach (array_keys($roomIds) as $roomId) {
                Room::find($roomId)?->syncStatus();
            }
        });

        if ($due->isNotEmpty() || $released > 0) {
            try {
                AuditLogger::log([
                    'action'      => 'UPDATE',
                    'module'      => 'RESERVATIONS',
                    'description' => 'Auto-expired ' . $due->count() . ' reservation(s) past check-out'
                        . ($due->isNotEmpty() ? ' (' . $due->pluck('reference_no')->implode(', ') . ')' : '')
                        . "; released {$released} tenant(s) from their rooms.",
                    'status'      => 'warning',
                    'user_name'   => 'System',
                    'user_role'   => 'system',
                ]);
            } catch (\Throwable $e) {
                report($e);
            }

            try {
                event(new ReservationUpdated('expired', $due->count() === 1 ? $due->first()->id : null));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return [
            'expired'  => $due->pluck('reference_no')->all(),
            'released' => $released,
        ];
    }

    /** Release tenants whose current stay is this reservation. */
    private function releaseTenantsOf(int $reservationId, array &$roomIds): int
    {
        $tenants = Tenant::where('approved_reservation_id', $reservationId)
            ->whereNotNull('room_id')
            ->get();

        foreach ($tenants as $tenant) {
            if ($tenant->room_id) $roomIds[$tenant->room_id] = true;
            $tenant->update(['room_id' => null]); // free the room; Active/Inactive follows the 6-month rule
        }

        return $tenants->count();
    }
}
