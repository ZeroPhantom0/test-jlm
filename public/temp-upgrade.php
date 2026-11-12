<?php
/**
 * ⚠️ TEMPORARY one-time upgrade — DELETES ITSELF after a successful run.
 *
 * Updates the production database for everything changed in this release,
 * without SSH and without needing any route:
 *
 *   1. Clears cached config / routes  (why new routes were 404-ing)
 *   2. Runs the new migrations
 *   3. Sets preference defaults for existing users
 *   4. Expires stays past check-out, frees their rooms, re-syncs room status
 *   5. Corrects EVERY tenant status by the 6-month rule (both ways) and
 *      emails each Inactive tenant who was not notified yet
 *   6. (optional, &enroll=1) puts already-approved reservations under the
 *      payment policy — any past check-in with unpaid balance is CANCELLED
 *   7. Clears all caches
 *
 * Preview (changes nothing):
 *   https://jlmcozycabin.online/temp-upgrade.php?key=24eb8c14b3ca128c6857dd1bdaf76633
 * Apply:
 *   https://jlmcozycabin.online/temp-upgrade.php?key=24eb8c14b3ca128c6857dd1bdaf76633&apply=1
 *
 * Works whether this file sits in public_html/ or public_html/public/.
 */

const UPGRADE_KEY = '24eb8c14b3ca128c6857dd1bdaf76633';

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');

if (! isset($_GET['key']) || ! hash_equals(UPGRADE_KEY, (string) $_GET['key'])) {
    http_response_code(404);
    exit("Not found\n");
}

@set_time_limit(300);
ini_set('display_errors', '1');
error_reporting(E_ALL);

$apply  = ! empty($_GET['apply']);
$enroll = ! empty($_GET['enroll']);

// ---------- Locate the Laravel app (public_html/ or public_html/public/) ----------
$base = null;
foreach ([__DIR__ . '/..', __DIR__] as $candidate) {
    if (is_file($candidate . '/bootstrap/app.php') && is_file($candidate . '/vendor/autoload.php')) {
        $base = realpath($candidate);
        break;
    }
}
if (! $base) {
    exit("Could not find the Laravel app (bootstrap/app.php) next to or above this file.\n");
}

$out = [];
$say = function (string $line = '') use (&$out) { $out[] = $line; };

$say($apply ? '=== APPLYING UPGRADE ===' : '=== PREVIEW — nothing is changed. Add &apply=1 to run. ===');
$say('App folder: ' . $base);
$say();

// ---------- 1. Clear cached config/routes BEFORE booting ----------
$say('[1] Cached config / routes / services');
foreach (['config.php', 'routes-v7.php', 'routes.php', 'services.php', 'packages.php', 'events.php'] as $f) {
    $path = $base . '/bootstrap/cache/' . $f;
    if (is_file($path)) {
        if ($apply) {
            @unlink($path);
            $say("    removed bootstrap/cache/{$f}");
        } else {
            $say("    found bootstrap/cache/{$f} (will be removed)");
        }
    }
}
$say();

// ---------- Boot Laravel (console kernel: Artisan + services available) ----------
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    $say('Time: ' . now()->toDayDateTimeString() . ' (' . config('app.timezone') . ')');
    $say();

    // ---------- 2. Migrations ----------
    $say('[2] Database migrations');
    Artisan::call('migrate:status', ['--pending' => true]);
    $pending = trim(Artisan::output());
    $say('    ' . str_replace("\n", "\n    ", $pending ?: 'No pending migrations.'));

    // A "create_X_table" migration that is pending although table X already
    // exists (e.g. `sessions` created earlier by hand) would crash migrate.
    // Record it as already run instead of creating the table twice.
    if (Schema::hasTable('migrations')) {
        $ran   = DB::table('migrations')->pluck('migration')->all();
        $batch = (int) DB::table('migrations')->max('batch') + 1;

        foreach (glob($base . '/database/migrations/*.php') as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $ran, true)) continue;

            if (preg_match('/_create_(\w+)_table$/', $name, $m) && Schema::hasTable($m[1])) {
                if ($apply) {
                    DB::table('migrations')->insert(['migration' => $name, 'batch' => $batch]);
                    $say("    {$name}: table `{$m[1]}` already exists → recorded as done (not re-created)");
                } else {
                    $say("    {$name}: table `{$m[1]}` already exists → will be recorded as done (not re-created)");
                }
            }
        }
    }

    if ($apply) {
        Artisan::call('migrate', ['--force' => true]);
        $say('    ' . str_replace("\n", "\n    ", trim(Artisan::output()) ?: 'Done.'));
    }
    $say();

    $ready = Schema::hasColumn('reservations', 'balance_tracked_at')
        && Schema::hasColumn('users', 'email_notifications')
        && Schema::hasTable('user_notifications')
        && Schema::hasColumn('tenants', 'inactivity_notified_at');

    if (! $ready) {
        $say('The next steps need the migrations above. Run again with &apply=1.');
        echo implode("\n", $out) . "\n";
        exit;
    }

    // ---------- 3. User preference defaults ----------
    $say('[3] User preferences (Email + SMS on, admin session timeout 2 hours)');
    $missing = DB::table('users')->where(fn ($q) => $q->whereNull('email_notifications')
        ->orWhereNull('sms_notifications')->orWhereNull('session_timeout'))->count();
    if ($apply && $missing) {
        DB::table('users')->whereNull('email_notifications')->update(['email_notifications' => 1]);
        DB::table('users')->whereNull('sms_notifications')->update(['sms_notifications' => 1]);
        DB::table('users')->whereNull('session_timeout')->update(['session_timeout' => 120]);
    }
    $say("    Users missing a value: {$missing}" . ($apply && $missing ? ' → fixed' : ''));
    $say();

    // ---------- 4. Expire finished stays + room re-sync ----------
    $say('[4] Expire stays past check-out, free rooms, re-sync room status');
    $expiry = app(App\Services\ReservationExpiryService::class)->run(dryRun: ! $apply);
    $say('    Reservations ' . ($apply ? 'expired' : 'to expire') . ': '
        . ($expiry['expired'] ? implode(', ', $expiry['expired']) : 'none'));
    $say('    Rooms freed: ' . $expiry['released']);
    if ($apply) {
        $before = App\Models\Room::pluck('status', 'room_number');
        App\Models\Room::all()->each->syncStatus();
        $after = App\Models\Room::pluck('status', 'room_number');
        $changed = $after->filter(fn ($s, $n) => ($before[$n] ?? null) !== $s);
        $say('    Room status: ' . ($changed->isEmpty() ? 'no changes'
            : $changed->map(fn ($s, $n) => "Room {$n} {$before[$n]} → {$s}")->implode('; ')));
    }
    $say();

    // ---------- 5. Tenant status: correct every tenant + email the inactive ----------
    $say('[5] Tenant status by the 6-month rule (Active if a room / open reservation / unsettled');
    $say('    billing / reservation in the last 6 months — otherwise Inactive + one email)');
    $say();

    $rec = app(App\Services\TenantInactivityService::class)
        ->reconcile(dryRun: ! $apply, sendNow: true); // send now — don't depend on the queue

    // works on PHP 8.2 (mb_str_pad only exists from 8.3)
    $pad = fn ($s, $n) => (string) $s . str_repeat(' ', max(1, $n - mb_strlen((string) $s)));
    $say('    ' . $pad('TENANT', 30) . $pad('LAST ACTIVITY', 15) . $pad('STATUS', 22) . $pad('REASON', 38) . 'EMAIL');
    $say('    ' . str_repeat('-', 125));
    foreach ($rec['rows'] as $row) {
        $status = $row['from'] === $row['to']
            ? ucfirst($row['to']) . ' (ok)'
            : ucfirst($row['from']) . ' → ' . ucfirst($row['to']);
        $say('    ' . $pad(mb_strimwidth($row['tenant'], 0, 28, '…'), 30) . $pad($row['last'], 15)
            . $pad($status, 22) . $pad($row['why'], 38) . $row['email']);
    }
    $say();
    $say("    Summary: {$rec['activated']} " . ($apply ? 'set' : 'to set') . " Active · {$rec['inactivated']} "
        . ($apply ? 'set' : 'to set') . " Inactive · {$rec['emailed']} email(s) " . ($apply ? 'sent' : 'to send')
        . ($rec['email_failed'] ? " · {$rec['email_failed']} FAILED (check mail settings in .env)" : ''));
    $say();

    // ---------- 7. Payment policy for already-approved reservations ----------
    $say('[6] Payment policy for reservations approved before this release (optional: &enroll=1)');
    $policy = app(App\Services\BalanceReminderService::class);
    $refs = $policy->enrollExisting(dryRun: true);
    if (! $refs) {
        $say('    None — all approved reservations are fully paid or already tracked.');
    } else {
        foreach ($refs as $ref) {
            $r = App\Models\Reservation::where('reference_no', $ref)->first();
            $in = $r ? $policy->timeIn($r) : null;
            $say("    {$ref} · {$r?->tenant_name} · balance PHP " . number_format($policy->outstandingBalance($r), 2)
                . ' · check-in ' . ($in?->format('M j, Y g:i A') ?? '—')
                . ($in && $in->isPast() ? '  ← WILL BE CANCELLED' : '  ← will get the 3-hour reminder'));
        }
        if ($apply && $enroll) {
            $done = $policy->enrollExisting(dryRun: false);
            $run  = $policy->run();
            $say('    Enrolled: ' . implode(', ', $done) . " · cancelled: {$run['cancelled']} · reminders: {$run['reminded']}");
        } else {
            $say('    Not applied (add &apply=1&enroll=1 to apply).');
        }
    }
    $say();

    // ---------- 8. Clear caches ----------
    $say('[7] Clear caches');
    if ($apply) {
        Artisan::call('optimize:clear');
        $say('    ' . str_replace("\n", "\n    ", trim(Artisan::output()) ?: 'Done.'));
    } else {
        $say('    Will run optimize:clear.');
    }
    $say();

    if ($apply) {
        try {
            App\Services\AuditLogger::log([
                'action' => 'UPDATE', 'module' => 'SETTINGS', 'status' => 'warning',
                'description' => 'Ran temporary production upgrade script' . ($enroll ? ' (incl. payment-policy enrollment)' : '') . '.',
                'user_name' => 'System', 'user_role' => 'system',
            ]);
        } catch (Throwable $e) {
            // logging is optional
        }

        $deleted = @unlink(__FILE__);
        $say('=== DONE ===');
        $say($deleted
            ? 'This file has deleted itself.'
            : '⚠️ Could not delete this file automatically — DELETE temp-upgrade.php NOW.');
    } else {
        $say('=== END OF PREVIEW ===');
    }
} catch (Throwable $e) {
    $say();
    $say('ERROR: ' . $e->getMessage());
    $say('  at ' . $e->getFile() . ':' . $e->getLine());
    $say('Nothing after this point was run. Fix the error and open the page again.');
}

echo implode("\n", $out) . "\n";
