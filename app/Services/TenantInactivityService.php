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
     * Correct EVERY tenant's status to what the 6-month rule says it should
     * be, in both directions, and email each tenant who is Inactive and has
     * not been notified yet (including ones that were already Inactive).
     *
     * @param bool $sendNow send emails immediately instead of queueing them
     * @return array{rows: array<int,array>, activated:int, inactivated:int, emailed:int, email_failed:int}
     */
    public function reconcile(bool $dryRun = false, bool $sendNow = false): array
    {
        $cutoff = now()->subMonths(self::MONTHS);
        $out = ['rows' => [], 'activated' => 0, 'inactivated' => 0, 'emailed' => 0, 'email_failed' => 0];

        // Tenants an admin removed from the list are left alone
        foreach (Tenant::with('user')->whereNull('removed_at')->get() as $tenant) {
            $user = $tenant->user;
            if (! $user || ! $user->isTenant()) continue;

            $last   = $this->lastActivityAt($tenant);
            $reason = match (true) {
                (bool) $tenant->room_id                  => 'has a room',
                $this->hasOngoingObligation($tenant)     => 'open reservation / unsettled billing',
                $last && $last->greaterThan($cutoff)     => 'reserved within 6 months',
                default                                  => null,
            };
            $should = $reason ? 'active' : 'inactive';
            $email  = $should === 'inactive' && ! $tenant->inactivity_notified_at && $user->email;

            $row = [
                'tenant' => $user->name,
                'last'   => $last?->format('M j, Y') ?? '—',
                'from'   => $tenant->status,
                'to'     => $should,
                'why'    => $reason ?? 'no reservation in 6 months',
                'email'  => $email ? ($dryRun ? 'will send' : 'sent') : ($should === 'inactive' ? 'already notified' : '—'),
            ];

            if (! $dryRun) {
                if ($tenant->status !== $should) {
                    $tenant->update(['status' => $should]); // → active clears the notice flag
                    $should === 'active' ? $out['activated']++ : $out['inactivated']++;
                }

                if ($email) {
                    try {
                        $mail = new TenantInactiveMail($user, $last);
                        $sendNow ? Mail::to($user->email)->send($mail) : Mail::to($user->email)->queue($mail);
                        $tenant->forceFill(['inactivity_notified_at' => now()])->save();
                        $out['emailed']++;
                    } catch (\Throwable $e) {
                        $row['email'] = 'FAILED: ' . $e->getMessage();
                        $out['email_failed']++;
                        Log::warning('[inactivity] email failed', ['tenant' => $tenant->id, 'error' => $e->getMessage()]);
                    }
                }
            } else {
                if ($tenant->status !== $should) {
                    $should === 'active' ? $out['activated']++ : $out['inactivated']++;
                }
                if ($email) $out['emailed']++;
            }

            $out['rows'][] = $row;
        }

        if (! $dryRun && ($out['activated'] || $out['inactivated'] || $out['emailed'])) {
            try {
                AuditLogger::log([
                    'action'      => 'UPDATE',
                    'module'      => 'TENANTS',
                    'description' => "Tenant status check (6-month rule): {$out['activated']} set Active, "
                        . "{$out['inactivated']} set Inactive, {$out['emailed']} inactivity email(s) sent.",
                    'status'      => 'warning',
                    'user_name'   => 'System',
                    'user_role'   => 'system',
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $out;
    }

    /**
     * Daily job: same rule as reconcile(). Kept for the command's output shape.
     *
     * @return array{inactivated: array<int,array{tenant:string,last:string}>, emailed: int}
     */
    public function run(bool $dryRun = false): array
    {
        $r = $this->reconcile($dryRun);

        return [
            'inactivated' => collect($r['rows'])
                ->filter(fn ($row) => $row['from'] === 'active' && $row['to'] === 'inactive')
                ->map(fn ($row) => ['tenant' => $row['tenant'], 'last' => $row['last']])
                ->values()->all(),
            'emailed' => $r['emailed'],
        ];
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

        foreach (Tenant::with('user')->whereNull('removed_at')->where('status', 'inactive')->get() as $tenant) {
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

        // Unsettled billing — ignoring invoices of cancelled / rejected
        // reservations (forfeited balances Billing no longer collects)
        $closedRefs = Reservation::where('user_id', $tenant->user_id)
            ->whereIn('status', ['Cancelled', 'Rejected'])
            ->pluck('reference_no')
            ->filter()
            ->all();

        return Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['Unpaid', 'Partial', 'Pending', 'Overdue'])
            ->where('balance', '>', 0.005)
            ->get()
            ->contains(function (Invoice $inv) use ($closedRefs) {
                foreach ($closedRefs as $ref) {
                    if (preg_match('/' . preg_quote($ref, '/') . '(?!\d)/', (string) $inv->description)) {
                        return false; // belongs to a closed reservation
                    }
                }
                return true;
            });
    }
}
