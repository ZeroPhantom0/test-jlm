<?php

namespace App\Console\Commands;

use App\Services\ReservationExpiryService;
use Illuminate\Console\Command;

class ExpirePastReservations extends Command
{
    protected $signature = 'reservations:expire-past
                            {--dry-run : Report without updating}';

    protected $description = 'Expire Approved reservations past their check-out time and release their tenants/rooms.';

    public function handle(ReservationExpiryService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $result = $service->run($dryRun);

        if (empty($result['expired']) && $result['released'] === 0) {
            $this->info('No stale reservations.');
            return self::SUCCESS;
        }

        foreach ($result['expired'] as $ref) {
            $this->line(($dryRun ? '  [dry] ' : '  ') . "Expired {$ref}");
        }
        $this->info(($dryRun ? '[dry] ' : '') . "Expired: " . count($result['expired']) . " · Tenants released: {$result['released']}");

        if ($dryRun) {
            $this->warn('Dry run — nothing changed.');
        }

        return self::SUCCESS;
    }
}
