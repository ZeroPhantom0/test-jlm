<?php
// app/Services/TenantInactivityService.php

namespace App\Services;

use App\Mail\TenantInactiveMail;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tenant status = Active / Inactive, driven by reservation activity.
 *
 *  - A tenant with no reservation activity for 6 months becomes Inactive and
 *    gets one email (Privacy Policy → "Inactivity Notification – After 6
 *    Months"). They can still log in; a new approved reservation makes them
 *    Active again (Tenant model clears the notice flag).
 *  - Never made inactive while something is ongoing: a current or upcoming
 *    stay, a pending reservation, an assigned room, or unsettled billing.
 *
 * "Reservation activity" = the latest of: a reservation being made, a stay's
 * check-out, or the tenant record being created (for brand-new tenants).
 */
class TenantInactivityService
{
    public const MONTHS = 6;

    /**
     * @return array{inactivated: array<int,array{tenant:string,last:string}>, emailed: int}
     */
    public function run(bool $dryRun = false): array
    {
        $cutoff = now()->subMonths(self::MONTHS);
        $result = ['inactivated' => [], 'emailed' => 0];

        $candidates = Tenant::with('user')
            ->where('status', 'active')
            ->whereNull('room_id')
            ->get();

        foreach ($candidates as $tenant) {
            if (! $tenant->user || ! $tenant->user->isTenant()) continue;
            if ($this->hasOngoingObligation($tenant)) continue;

            $last = $this->lastActivityAt($tenant);
            if ($last && $last->greaterThan($cutoff)) continue;

            $result['inactivated'][] = [
                'tenant' => $tenant->user->name,
                'last'   => $last?->format('M j, Y') ?? '—',
            ];
            if ($dryRun) continue;

            $tenant->update(['status' => 'inactive', 'inactivity_notified_at' => now()]);

            try {
                if ($tenant->user->email) {
                    Mail::to($tenant->user->email)->queue(new TenantInactiveMail($tenant->user, $last));
                    $result['emailed']++;
                }
            } catch (\Throwable $e) {
                Log::warning('[inactivity] email failed', ['tenant' => $tenant->id, 'error' => $e->getMessage()]);
            }
        }

        if (! $dryRun && count($result['inactivated']) > 0) {
            try {
                AuditLogger::log([
                    'action'      => 'UPDATE',
                    'module'      => 'TENANTS',
                    'description' => 'Marked ' . count($result['inactivated']) . ' tenant(s) inactive after '
                        . self::MONTHS . ' months without a reservation: '
                        . collect($result['inactivated'])->pluck('tenant')->implode(', ') . '.',
                    'status'      => 'warning',
                    'user_name'   => 'System',
                    'user_role'   => 'system',
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $result;
    }

    /**
     * Inactive tenants who DID have reservation activity within 6 months →
     * back to Active. Used once to repair tenants that the old expiry logic
     * set inactive the moment their stay ended.
     *
     * @return string[] names reactivated
     */
    public function reactivateRecentlyActive(bool $dryRun = false): array
    {
        $cutoff = now()->subMonths(self::MONTHS);
        $names  = [];

        foreach (Tenant::with('user')->where('status', 'inactive')->get() as $tenant) {
            if (! $tenant->user || ! $tenant->user->isTenant()) continue;

            $last = $this->lastActivityAt($tenant);
            if ($last && $last->greaterThan($cutoff)) {
                $names[] = $tenant->user->name;
                if (! $dryRun) {
                    $tenant->update(['status' => 'active']); // model hook clears the notice flag
                }
            }
        }

        return $names;
    }

    public function lastActivityAt(Tenant $tenant): ?Carbon
    {
        $dates = collect([$tenant->created_at]);

        Reservation::where('user_id', $tenant->user_id)->get()->each(function (Reservation $r) use ($dates) {
            $dates->push($r->created_at);
            if (in_array($r->status, ['Approved', 'Expired'], true)) {
                $dates->push($r->checkOutAt());
            }
        });

        return $dates->filter()->map(fn ($d) => Carbon::parse($d))->sort()->last();
    }

    private function hasOngoingObligation(Tenant $tenant): bool
    {
        // Pending, current or upcoming reservation
        $openReservation = Reservation::where('user_id', $tenant->user_id)
            ->where(function ($q) {
                $q->where('status', 'Pending')
                    ->orWhere(fn ($q2) => $q2->where('status', 'Approved'));
            })
            ->get()
            ->contains(fn (Reservation $r) => $r->status === 'Pending' || ! $r->isStayOver());
        if ($openReservation) return true;

        // Unsettled billing
        return Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['Unpaid', 'Partial', 'Pending', 'Overdue'])
            ->where('balance', '>', 0.005)
            ->exists();
    }
}
