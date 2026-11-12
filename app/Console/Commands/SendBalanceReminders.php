<?php
// app/Console/Commands/SendBalanceReminders.php

namespace App\Console\Commands;

use App\Services\BalanceReminderService;
use Illuminate\Console\Command;

class SendBalanceReminders extends Command
{
    protected $signature = 'billing:balance-reminders
                            {--enroll-existing : Also apply the payment policy to Approved reservations approved before it existed}
                            {--dry-run : With --enroll-existing, only list what would be enrolled}';

    protected $description = 'Send 3-hour remaining-balance SMS reminders, apply the payment policy at time-in, and send cancellation SMS 5 minutes after cancellation.';

    public function handle(BalanceReminderService $service): int
    {
        if ($this->option('enroll-existing')) {
            $dry  = (bool) $this->option('dry-run');
            $refs = $service->enrollExisting($dry);

            $this->info(($dry ? '[dry] Would enroll: ' : 'Enrolled: ') . (count($refs) ? implode(', ', $refs) : 'none'));
            if ($dry) {
                return self::SUCCESS;
            }
        }

        $result = $service->run();

        $this->info(sprintf(
            'Reminders sent: %d · Cancelled (payment policy): %d · Cancellation SMS sent: %d',
            $result['reminded'],
            $result['cancelled'],
            $result['notified']
        ));

        return self::SUCCESS;
    }
}
