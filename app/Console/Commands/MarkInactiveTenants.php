<?php
// app/Console/Commands/MarkInactiveTenants.php

namespace App\Console\Commands;

use App\Services\TenantInactivityService;
use Illuminate\Console\Command;

class MarkInactiveTenants extends Command
{
    protected $signature = 'tenants:mark-inactive {--dry-run : List who would be marked inactive without changing anything}';

    protected $description = 'Mark tenants Inactive after 6 months without a reservation and email them once.';

    public function handle(TenantInactivityService $service): int
    {
        $dry    = (bool) $this->option('dry-run');
        $result = $service->run($dry);

        if (empty($result['inactivated'])) {
            $this->info('No tenants to mark inactive.');
            return self::SUCCESS;
        }

        foreach ($result['inactivated'] as $row) {
            $this->line(($dry ? '  [dry] ' : '  ') . "{$row['tenant']} — last reservation activity {$row['last']}");
        }
        $this->info(($dry ? '[dry] Would mark ' : 'Marked ') . count($result['inactivated']) . ' tenant(s) inactive'
            . ($dry ? '.' : " · emails queued: {$result['emailed']}"));

        return self::SUCCESS;
    }
}
