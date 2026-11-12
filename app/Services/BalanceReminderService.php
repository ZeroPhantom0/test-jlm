<?php
// app/Services/BalanceReminderService.php

namespace App\Services;

use App\Events\InvoiceUpdated;
use App\Events\ReservationUpdated;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Remaining-balance SMS reminders + payment-policy cancellation.
 *
 * Condition (relative to time-in)        Behaviour
 * ------------------------------------   ---------------------------------------------
 * More than 3 hours before time-in       Reminder is scheduled for 3 hours before.
 * Exactly / less than 3 hours before     Reminder is sent immediately on approval.
 * Time-in already passed                 No reminder; payment policy applies
 *                                        (reservation is cancelled).
 * Fully paid                             Nothing is sent.
 *
 * After a payment-policy cancellation the tenant is texted 5 minutes later.
 *
 * Invoices in "Pending" status (proof of payment submitted, awaiting admin
 * verification) are treated as settled for reminder / cancellation purposes,
 * so a tenant who has already paid is never chased or cancelled.
 */
class BalanceReminderService
{
    private const OUTSTANDING_STATUSES = ['Unpaid', 'Partial'];
    private const BRAND = 'JLM Cozy Cabin';

    // ============================================================
    // ENTRY POINTS
    // ============================================================

    /**
     * Call right after a reservation is approved (and its invoice settled).
     */
    public function handleApproval(Reservation $reservation): void
    {
        $reservation->refresh();

        $balance = $this->outstandingBalance($reservation);

        // Full payment → no remaining-balance reminder.
        if ($balance <= 0) {
            $reservation->forceFill(['balance_tracked_at' => null])->save();
            return;
        }

        // Enrol the reservation so the scheduler picks it up.
        $reservation->forceFill([
            'balance_tracked_at'       => now(),
            'balance_reminder_sent_at' => null,
        ])->save();

        $timeIn = $this->timeIn($reservation);
        if (! $timeIn) {
            return;
        }

        // Time-in already passed → no reminder; the scheduler applies the policy.
        if ($timeIn->lessThanOrEqualTo(now())) {
            return;
        }

        // Exactly / less than 3 hours before time-in → send now.
        if (now()->greaterThanOrEqualTo($this->reminderAt($timeIn))) {
            $this->sendReminder($reservation, $balance, $timeIn);
        }

        // Otherwise it stays scheduled: the minute-by-minute command sends it
        // once now() reaches time-in minus 3 hours.
    }

    /**
     * Run every minute from the scheduler.
     *
     * @return array{reminded:int, cancelled:int, notified:int}
     */
    public function run(): array
    {
        return [
            'reminded'  => $this->sendDueReminders(),
            'cancelled' => $this->applyPaymentPolicy(),
            'notified'  => $this->sendDueCancellationNotices(),
        ];
    }

    // ============================================================
    // SCHEDULED STEPS
    // ============================================================

    /** Reminders whose 3-hour window has opened (and time-in hasn't passed). */
    public function sendDueReminders(): int
    {
        $sent = 0;

        foreach ($this->trackedApproved()->whereNull('balance_reminder_sent_at')->get() as $reservation) {
            $timeIn = $this->timeIn($reservation);
            if (! $timeIn || $timeIn->lessThanOrEqualTo(now())) {
                continue; // passed → handled by applyPaymentPolicy()
            }
            if (now()->lessThan($this->reminderAt($timeIn))) {
                continue; // still more than 3 hours away
            }

            $balance = $this->outstandingBalance($reservation);
            if ($balance <= 0) {
                continue;
            }

            if ($this->sendReminder($reservation, $balance, $timeIn)) {
                $sent++;
            }
        }

        return $sent;
    }

    /** Cancel tracked reservations whose time-in passed with a balance still owed. */
    public function applyPaymentPolicy(): int
    {
        $cancelled = 0;

        foreach ($this->trackedApproved()->get() as $reservation) {
            $timeIn = $this->timeIn($reservation);
            if (! $timeIn || $timeIn->greaterThan(now())) {
                continue;
            }

            $balance = $this->outstandingBalance($reservation);
            if ($balance <= 0) {
                // Settled in time → stop tracking.
                $reservation->forceFill(['balance_tracked_at' => null])->save();
                continue;
            }

            if ($this->cancelForNonPayment($reservation, $balance)) {
                $cancelled++;
            }
        }

        return $cancelled;
    }

    /** Text tenants 5 minutes after a payment-policy cancellation. */
    public function sendDueCancellationNotices(): int
    {
        $delay = (int) config('services.balance_reminders.cancel_sms_delay_mins', 5);
        $sent  = 0;

        $due = Reservation::query()
            ->where('status', 'Cancelled')
            ->whereNotNull('cancelled_at')
            ->whereNull('cancellation_sms_sent_at')
            ->where('cancelled_at', '<=', now()->subMinutes($delay))
            ->get();

        foreach ($due as $reservation) {
            if (! $this->claim($reservation, 'cancellation_sms_sent_at')) {
                continue;
            }

            $phone = $this->phoneFor($reservation);
            if (! $phone) {
                Log::warning('[balance-reminder] No phone for cancellation SMS', ['reservation' => $reservation->reference_no]);
                continue;
            }

            $balance = $this->cancelledBalance($reservation);
            $message = $this->cancellationMessage($reservation, $balance, $this->timeIn($reservation));

            if (SmsService::send($phone, $message)) {
                $sent++;
                $this->audit("Sent payment-policy cancellation SMS for reservation {$reservation->reference_no}", $reservation);
            } else {
                Log::warning('[balance-reminder] Cancellation SMS failed', ['reservation' => $reservation->reference_no]);
            }
        }

        return $sent;
    }

    // ============================================================
    // ACTIONS
    // ============================================================

    private function sendReminder(Reservation $reservation, float $balance, Carbon $timeIn): bool
    {
        // Claim first so the approval request and the scheduler can never double-send.
        if (! $this->claim($reservation, 'balance_reminder_sent_at')) {
            return false;
        }

        $phone = $this->phoneFor($reservation);
        if (! $phone) {
            Log::warning('[balance-reminder] No phone for reminder', ['reservation' => $reservation->reference_no]);
            return false;
        }

        $ok = SmsService::send($phone, $this->reminderMessage($reservation, $balance, $timeIn));

        if ($ok) {
            $this->audit("Sent remaining-balance SMS reminder for reservation {$reservation->reference_no}", $reservation);
        } else {
            Log::warning('[balance-reminder] Reminder SMS failed', ['reservation' => $reservation->reference_no]);
        }

        return $ok;
    }

    private function cancelForNonPayment(Reservation $reservation, float $balance): bool
    {
        $invoiceIds = [];

        $changed = DB::transaction(function () use ($reservation, &$invoiceIds) {
            // Re-check under lock so two scheduler runs can't both cancel.
            $locked = Reservation::whereKey($reservation->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== 'Approved' || ! $locked->balance_tracked_at) {
                return false;
            }

            $locked->forceFill([
                'status'                   => 'Cancelled',
                'cancelled_at'             => now(),
                'cancellation_sms_sent_at' => null,
                'balance_tracked_at'       => null,
                'admin_notes'              => trim(($locked->admin_notes ? $locked->admin_notes . "\n" : '')
                    . 'Auto-cancelled ' . now()->format('M j, Y g:i A')
                    . ': remaining balance not settled before check-in (Payment Policy).'),
            ])->save();

            // Release the tenant + room held by this reservation.
            Tenant::where('approved_reservation_id', $locked->id)
                ->update(['status' => 'inactive']);

            $locked->room?->syncStatus();

            $invoiceIds = $this->outstandingInvoices($locked)->pluck('id')->all();

            return true;
        });

        if (! $changed) {
            return false;
        }

        $this->audit(
            "Auto-cancelled reservation {$reservation->reference_no} ({$reservation->tenant_name}): "
            . 'remaining balance of ' . $this->money($balance) . ' unpaid at check-in.',
            $reservation,
            'CANCEL',
            'warning'
        );

        event(new ReservationUpdated('cancelled', $reservation->id));
        if (count($invoiceIds) === 1) {
            event(new InvoiceUpdated('updated', $invoiceIds[0]));
        } elseif (count($invoiceIds) > 1) {
            event(new InvoiceUpdated('updated', null));
        }

        return true;
    }

    // ============================================================
    // MESSAGES  (kept GSM-safe: "PHP" instead of the peso sign)
    // ============================================================

    public function reminderMessage(Reservation $reservation, float $balance, Carbon $timeIn): string
    {
        return sprintf(
            "%s: Hi %s, this is a friendly reminder that your reservation %s has a remaining balance of %s. "
            . "Kindly settle it before your check-in on %s to keep your booking. "
            . "You may pay through your tenant portal. Unsettled balances will result in cancellation and the down payment is non-refundable. Thank you!",
            self::BRAND,
            $this->firstName($reservation),
            $reservation->reference_no,
            $this->money($balance),
            $timeIn->format('M j, Y \a\t g:i A')
        );
    }

    public function cancellationMessage(Reservation $reservation, float $balance, ?Carbon $timeIn): string
    {
        return sprintf(
            "%s: Hi %s, we regret to inform you that your reservation %s has been cancelled because the remaining balance%s was not settled before your check-in%s. "
            . "As stated in our Payment Policy, the down payment is non-refundable. "
            . "For questions or to make a new reservation, please contact us through your tenant portal. Thank you.",
            self::BRAND,
            $this->firstName($reservation),
            $reservation->reference_no,
            $balance > 0 ? ' of ' . $this->money($balance) : '',
            $timeIn ? ' on ' . $timeIn->format('M j, Y \a\t g:i A') : ''
        );
    }

    // ============================================================
    // HELPERS
    // ============================================================

    private function trackedApproved()
    {
        return Reservation::query()
            ->where('status', 'Approved')
            ->whereNotNull('balance_tracked_at');
    }

    /** Check-in date + time (falls back to the configured default time). */
    public function timeIn(Reservation $reservation): ?Carbon
    {
        if (! $reservation->check_in) {
            return null;
        }

        $time = trim((string) ($reservation->check_in_time ?: config('services.balance_reminders.default_check_in_time', '14:00')));

        try {
            return Carbon::parse($reservation->check_in->toDateString() . ' ' . $time);
        } catch (\Throwable $e) {
            return $reservation->check_in->copy()->startOfDay();
        }
    }

    private function reminderAt(Carbon $timeIn): Carbon
    {
        return $timeIn->copy()->subHours((int) config('services.balance_reminders.hours_before', 3));
    }

    private function invoicesFor(Reservation $reservation)
    {
        return Invoice::query()->where('description', 'like', '%' . $reservation->reference_no . '%');
    }

    private function outstandingInvoices(Reservation $reservation): Collection
    {
        return $this->invoicesFor($reservation)
            ->whereIn('status', self::OUTSTANDING_STATUSES)
            ->where('balance', '>', 0.005)
            ->get();
    }

    public function outstandingBalance(Reservation $reservation): float
    {
        // A submitted payment awaiting verification counts as settled.
        if ($this->invoicesFor($reservation)->where('status', 'Pending')->exists()) {
            return 0.0;
        }

        return round((float) $this->outstandingInvoices($reservation)->sum('balance'), 2);
    }

    /** Balance that was left when the reservation got cancelled (for the SMS). */
    private function cancelledBalance(Reservation $reservation): float
    {
        return round((float) $this->outstandingInvoices($reservation)->sum('balance'), 2);
    }

    /** Atomically mark a "sent" column; returns false if someone else already did. */
    private function claim(Reservation $reservation, string $column): bool
    {
        $claimed = Reservation::whereKey($reservation->id)
            ->whereNull($column)
            ->update([$column => now()]) === 1;

        if ($claimed) {
            $reservation->setAttribute($column, now());
        }

        return $claimed;
    }

    private function phoneFor(Reservation $reservation): ?string
    {
        return SmsService::normalizePhone($reservation->phone ?: $reservation->user?->phone);
    }

    private function firstName(Reservation $reservation): string
    {
        $name = trim((string) ($reservation->tenant_name ?: $reservation->user?->name));
        return $name === '' ? 'Tenant' : explode(' ', $name)[0];
    }

    private function money(float $amount): string
    {
        return 'PHP ' . number_format($amount, 2);
    }

    private function audit(string $description, Reservation $reservation, string $action = 'NOTIFY', string $status = 'success'): void
    {
        try {
            AuditLogger::log([
                'action'       => $action,
                'module'       => 'BILLING',
                'description'  => $description,
                'status'       => $status,
                'subject_type' => Reservation::class,
                'subject_id'   => $reservation->id,
                'user_name'    => 'System',
                'user_role'    => 'system',
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
