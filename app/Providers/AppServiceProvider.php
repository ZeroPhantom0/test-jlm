<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Instantly mark the user as "offline" when they click Logout
        Event::listen(function (Logout $event) {
            if ($event->user) {
                cache()->forget('user_online_' . $event->user->id);
                cache()->forget('admin_online_' . $event->user->id);
            }
        });

        // Bell notifications: tenant actions → admins, admin actions → tenant,
        // announcements → all tenants (the actor never notifies themself)
        foreach ([
            \App\Events\ReservationUpdated::class,
            \App\Events\InvoiceUpdated::class,
            \App\Events\MaintenanceUpdated::class,
            \App\Events\ServiceRequestUpdated::class,
            \App\Events\AnnouncementUpdated::class,
        ] as $eventClass) {
            Event::listen($eventClass, [\App\Listeners\CreateBellNotifications::class, 'handle']);
        }

        // Start the admin idle-timeout clock fresh on every login
        Event::listen(function (\Illuminate\Auth\Events\Login $event) {
            try {
                session()->put(\App\Http\Middleware\EnforceSessionTimeout::SESSION_KEY, now()->timestamp);
            } catch (\Throwable $e) {
                // no session (e.g. API / console) — nothing to reset
            }
        });

        // "Email Notifications" preference: cancel notification emails to
        // users who switched it off. Security emails (login / verification
        // codes, password reset, registration) are NOT in this list and
        // always go out.
        Event::listen(function (MessageSending $event) {
            $mailable = $event->data['__laravel_mailable'] ?? null;
            if (! in_array($mailable, self::NOTIFICATION_MAILABLES, true)) {
                return null;
            }

            $to = array_map(fn ($a) => strtolower($a->getAddress()), $event->message->getTo());
            if (empty($to)) {
                return null;
            }

            $optedOut = User::whereIn(DB::raw('LOWER(email)'), $to)
                ->where('email_notifications', false)
                ->count();

            // Skip only when every recipient opted out
            if ($optedOut >= count($to)) {
                Log::info('Email skipped — recipient turned off Email Notifications', [
                    'mailable' => $mailable,
                    'to' => $to,
                ]);
                return false;
            }

            return null;
        });
    }

    /** Notification emails governed by the user's "Email Notifications" toggle. */
    private const NOTIFICATION_MAILABLES = [
        \App\Mail\PaymentApprovedMail::class,
        \App\Mail\PaymentRejectedMail::class,
        \App\Mail\PaymentVerifiedMail::class,
        \App\Mail\PaymentDueReminderMail::class,
        \App\Mail\OverduePaymentMail::class,
        \App\Mail\ReservationApprovedMail::class,
        \App\Mail\ReservationRejectedMail::class,
    ];
}