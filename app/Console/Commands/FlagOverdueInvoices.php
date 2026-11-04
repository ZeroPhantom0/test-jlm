<?php
// app/Console/Commands/FlagOverdueInvoices.php

namespace App\Console\Commands;

use App\Mail\OverduePaymentMail;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class FlagOverdueInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --dry-run  : show what would happen without writing anything
     * --force    : re-send emails even if already notified today
     */
    protected $signature = 'billing:flag-overdue
                            {--dry-run : Report what would change without updating}
                            {--force   : Re-send emails even if already notified today}';

    protected $description = 'Flip unpaid invoices past their due date to Overdue and email the tenant once per day.';

    public function handle(): int
    {
        $today = now()->toDateString();
        $dryRun = (bool) $this->option('dry-run');
        $force  = (bool) $this->option('force');

        // Which invoices qualify?
        //   - still has money owed
        //   - due_date strictly before today (i.e. yesterday or earlier)
        //   - not Paid / Pending (a Pending is awaiting admin action — don't stomp)
        $query = Invoice::query()
            ->whereIn('status', ['Unpaid', 'Partial'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->where('balance', '>', 0);

        if (! $force) {
            $query->where(function ($q) use ($today) {
                $q->whereNull('was_overdue_notified_at')
                  ->orWhereDate('was_overdue_notified_at', '<', $today);
            });
        }

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            $this->info('No overdue invoices to process.');
            return self::SUCCESS;
        }

        $this->info("Found {$invoices->count()} overdue candidate(s).");

        $flipped = 0;
        $emailed = 0;
        $skippedNoEmail = 0;

        foreach ($invoices as $invoice) {
            $daysOverdue = (int) now()->diffInDays($invoice->due_date);

            if ($dryRun) {
                $this->line(sprintf(
                    '  [dry] %s · %s · ₱%s · %d day(s) overdue',
                    $invoice->transaction_no,
                    $invoice->tenant_name,
                    number_format((float) $invoice->balance, 2),
                    $daysOverdue
                ));
                continue;
            }

            // 1) Flip the status if it isn't Overdue yet
            $needsStatusFlip = $invoice->status !== 'Overdue';
            if ($needsStatusFlip) {
                $invoice->update(['status' => 'Overdue']);
                $flipped++;
            }

            // 2) Send the email (once per day, unless --force)
            $tenantEmail = $this->resolveTenantEmail($invoice);
            $alreadyNotifiedToday = $invoice->was_overdue_notified_at
                && $invoice->was_overdue_notified_at->toDateString() === $today;

            if (! $tenantEmail) {
                $skippedNoEmail++;
                continue;
            }

            if ($alreadyNotifiedToday && ! $force) {
                continue;
            }

            try {
                Mail::to($tenantEmail)->send(
                    new OverduePaymentMail($invoice->fresh(), $daysOverdue)
                );

                $invoice->update(['was_overdue_notified_at' => now()]);
                $emailed++;
            } catch (\Throwable $e) {
                report($e);
                $this->error("  Mail failed for {$invoice->transaction_no}: {$e->getMessage()}");
            }
        }

        if ($dryRun) {
            $this->warn('Dry run — nothing was changed.');
            return self::SUCCESS;
        }

        // 3) Audit log — one entry summarising the run
        if ($flipped > 0 || $emailed > 0) {
            AuditLogger::log([
                'action'      => 'UPDATE',
                'module'      => 'BILLING',
                'description' => "Auto-flagged {$flipped} invoice(s) as Overdue and sent {$emailed} reminder email(s)."
                    . ($skippedNoEmail ? " {$skippedNoEmail} had no tenant email on file." : ''),
                'status'      => 'warning',
            ]);
        }

        $this->info("Done. Flipped: {$flipped} · Emailed: {$emailed} · No email: {$skippedNoEmail}");

        return self::SUCCESS;
    }

    private function resolveTenantEmail(Invoice $invoice): ?string
    {
        if (! $invoice->tenant_id) {
            return null;
        }

        $tenant = Tenant::with('user')->find($invoice->tenant_id);

        return $tenant?->user?->email;
    }
}