<?php

namespace App\Console\Commands;

use App\Models\TourScene;
use App\Models\VirtualTour;
use App\Support\ImageThumb;
use Illuminate\Console\Command;

/**
 * Pre-generates the small room-card / gallery images from the (huge) tour
 * thumbnails and 360° scenes. Needs PHP GD for this one command; if GD isn't
 * enabled in php.ini you can still run:
 *
 *   php -d extension=gd artisan rooms:thumbnails
 *
 * Once generated, the web server serves them even without GD.
 */
class GenerateRoomThumbnails extends Command
{
    protected $signature = 'rooms:thumbnails';

    protected $description = 'Generate resized thumbnails for virtual tour images used on room cards';

    public function handle(): int
    {
        if (! ImageThumb::available()) {
            $this->error('PHP GD extension is not loaded. Run: php -d extension=gd artisan rooms:thumbnails');
            return self::FAILURE;
        }

        $paths = VirtualTour::whereNotNull('thumbnail')->pluck('thumbnail')
            ->reject(fn ($t) => str_starts_with($t, 'http'))
            ->map(fn ($t) => 'tours/' . $t)
            ->merge(TourScene::whereNotNull('image_path')->pluck('image_path')->map(fn ($p) => 'scenes/' . $p))
            ->unique()
            ->values();

        $bar = $this->output->createProgressBar($paths->count() * 2);
        $made = 0;
        foreach ($paths as $path) {
            foreach ([ImageThumb::CARD, ImageThumb::GALLERY] as $width) {
                try {
                    $made += ImageThumb::generate($path, $width) ? 1 : 0;
                } catch (\Throwable $e) {
                    $this->newLine();
                    $this->warn("Skipped {$path}: {$e->getMessage()}");
                }
                $bar->advance();
            }
        }
        $bar->finish();
        $this->newLine();
        $this->info("Thumbnails ready for {$paths->count()} images ({$made} sizes).");

        return self::SUCCESS;
    }
}
