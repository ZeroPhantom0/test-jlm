<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Removes accounts that never finished onboarding (no personal information
 * or no identity verification). Since sign-up now only creates the account
 * after onboarding, these can only be leftovers from before that change.
 *
 * Kept on purpose (FKs are SET NULL): reservations, invoices and payment
 * proofs stay as history — revenue and reports don't change.
 * A JSON backup of every deleted user/tenant row is written first to
 * storage/app/private/purged-accounts/.
 */
class PurgeIncompleteAccounts extends Command
{
    protected $signature = 'accounts:purge-incomplete
                            {--dry-run : List the accounts without deleting}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Delete accounts that never completed onboarding (backs them up first)';

    public function handle(): int
    {
        $users = User::where('role', '!=', 'admin')
            ->where(fn ($q) => $q->doesntHave('personalInformation')->orDoesntHave('identityVerification'))
            ->get();

        if ($users->isEmpty()) {
            $this->info('No unfinished accounts found.');
            return self::SUCCESS;
        }

        $this->table(['ID', 'Email', 'Created', 'Personal info', 'ID verification'], $users->map(fn (User $u) => [
            $u->id, $u->email, $u->created_at,
            $u->personalInformation()->exists() ? 'yes' : 'no',
            $u->identityVerification()->exists() ? 'yes' : 'no',
        ]));

        if ($this->option('dry-run')) {
            $this->comment('Dry run — nothing deleted.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Delete these {$users->count()} account(s)? Reservations and invoices are kept as history.")) {
            return self::FAILURE;
        }

        // Backup first: a deleted user can't be recovered otherwise
        $backup = $users->map(fn (User $u) => [
            'user'   => $u->makeVisible(['password', 'remember_token'])->toArray(),
            'tenant' => Tenant::where('user_id', $u->id)->first()?->toArray(),
        ])->values();
        $file = 'purged-accounts/' . now()->format('Y-m-d_His') . '.json';
        Storage::disk('local')->put($file, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->line("Backup written to storage/app/private/{$file}");

        DB::transaction(function () use ($users) {
            foreach ($users as $u) {
                DB::table('sessions')->where('user_id', $u->id)->delete();
                $u->delete(); // tenants / personal info / codes cascade; reservations & invoices are kept (SET NULL)

                AuditLogger::log([
                    'action'       => 'DELETE',
                    'module'       => 'TENANTS',
                    'description'  => "Removed account that never finished onboarding: {$u->email} (#{$u->id})",
                    'status'       => 'warning',
                    'subject_type' => User::class,
                    'subject_id'   => $u->id,
                ]);
            }
        });

        $this->info("Deleted {$users->count()} unfinished account(s).");

        return self::SUCCESS;
    }
}
