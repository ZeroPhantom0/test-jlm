<?php
// app/Console/Commands/SendBalanceReminders.php

namespace App\Console\Commands;

use App\Services\BalanceReminderService;
use Illuminate\Console\Command;

class SendBalanceReminders extends Command
{
    protected $signature = 'billing:balance-reminders';

    protected $description = 'Send 3-hour remaining-balance SMS reminders, apply the payment policy at time-in, and send cancellation SMS 5 minutes after cancellation.';

    public function handle(BalanceReminderService $service): int
    {
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
