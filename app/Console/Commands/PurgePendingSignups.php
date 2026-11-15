<?php

namespace App\Console\Commands;

use App\Services\PendingSignup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes temporary uploads left by sign-ups that were never finished.
 * (No database rows exist for those — only these private files.)
 */
class PurgePendingSignups extends Command
{
    protected $signature = 'signup:purge-pending {--hours=24 : Remove drafts older than this many hours}';

    protected $description = 'Delete temporary uploads from sign-ups that were never completed';

    public function handle(): int
    {
        $disk   = Storage::disk('local');
        $cutoff = now()->subHours((int) $this->option('hours'))->getTimestamp();
        $removed = 0;

        foreach ($disk->directories(PendingSignup::DIR) as $dir) {
            $files  = $disk->allFiles($dir);
            $newest = collect($files)->map(fn ($f) => $disk->lastModified($f))->max() ?? 0;

            if ($newest < $cutoff) {
                $disk->deleteDirectory($dir);
                $removed++;
            }
        }

        $this->info("Removed {$removed} abandoned sign-up draft(s).");

        return self::SUCCESS;
    }
}
