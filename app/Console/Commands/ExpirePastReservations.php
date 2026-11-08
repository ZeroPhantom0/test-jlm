<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\Tenant;
use App\Services\AuditLogger;
use Illuminate\Console\Command;

class ExpirePastReservations extends Command
{
    protected $signature = 'reservations:expire-past
                            {--dry-run : Report without updating}';

    protected $description = 'Mark Approved reservations as Completed/Expired once check_out has passed.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $stale = Reservation::query()
            ->where('status', 'Approved')
            ->whereNotNull('check_out')
            ->whereDate('check_out', '<', now()->toDateString())
            ->get();

        if ($stale->isEmpty()) {
            $this->info('No stale reservations.');
            return self::SUCCESS;
        }

        $this->info("Found {$stale->count()} stale reservation(s).");

        if ($dryRun) {
            foreach ($stale as $r) {
                $this->line("  [dry] {$r->reference_no} · {$r->tenant_name} · check-out {$r->check_out->format('M j, Y')}");
            }
            $this->warn('Dry run — nothing changed.');
            return self::SUCCESS;
        }

        foreach ($stale as $r) {
            $r->update(['status' => 'Expired']); // or 'Completed' if you prefer
        }

        AuditLogger::log([
            'action'      => 'UPDATE',
            'module'      => 'RESERVATIONS',
            'description' => "Auto-expired {$stale->count()} reservation(s) past check-out.",
            'status'      => 'warning',
        ]);

        $this->info("Done. Expired: {$stale->count()}");
        return self::SUCCESS;
    }
}