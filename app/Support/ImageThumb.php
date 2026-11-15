<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Small, cached JPEG thumbnails for images on the public disk.
 *
 * Tour thumbnails / 360° scenes are full panoramas (often 8000×4000, ~8 MB),
 * far too heavy for room cards. The first request creates a resized copy in
 * storage/app/public/thumbs/{width}/…; later requests reuse it.
 *
 * Needs the PHP GD extension. Without it, the original image URL is returned
 * (everything still works, just slower to load).
 */
final class ImageThumb
{
    public const CARD    = 800;   // room cards, tiles
    public const GALLERY = 1600;  // detail drawer gallery

    public static function available(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatefromjpeg');
    }

    /**
     * @param  string  $relativePath  path on the public disk, e.g. "tours/abc.jpg"
     */
    public static function url(string $relativePath, int $width = self::CARD): string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($relativePath)) {
            return asset('storage/' . $relativePath);
        }

        $thumb = self::thumbPath($relativePath, $width);

        // Already generated (e.g. by `php artisan rooms:thumbnails`) → no GD needed
        if ($disk->exists($thumb)) {
            return asset('storage/' . $thumb);
        }

        if (! self::available()) {
            return asset('storage/' . $relativePath);
        }

        try {
            self::make($disk->path($relativePath), $disk->path($thumb), $width);
        } catch (\Throwable $e) {
            report($e);
            return asset('storage/' . $relativePath);
        }

        return asset('storage/' . $thumb);
    }

    /** Cache path; changes when the source file changes (mtime in the key). */
    public static function thumbPath(string $relativePath, int $width): string
    {
        $mtime = @filemtime(Storage::disk('public')->path($relativePath));
        return "thumbs/{$width}/" . md5($relativePath . '|' . $mtime) . '.jpg';
    }

    /** Create the thumbnail now (used by the artisan command). Returns false if GD is missing. */
    public static function generate(string $relativePath, int $width): bool
    {
        $disk = Storage::disk('public');
        if (! self::available() || ! $disk->exists($relativePath)) {
            return false;
        }
        $thumb = self::thumbPath($relativePath, $width);
        if (! $disk->exists($thumb)) {
            self::make($disk->path($relativePath), $disk->path($thumb), $width);
        }
        return true;
    }

    private static function make(string $source, string $target, int $width): void
    {
        [$w, $h, $type] = getimagesize($source) ?: [0, 0, 0];
        if (! $w || ! $h) {
            throw new \RuntimeException("Unreadable image: {$source}");
        }

        $src = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($source),
            IMAGETYPE_PNG  => imagecreatefrompng($source),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($source) : false,
            default        => false,
        };
        if (! $src) {
            throw new \RuntimeException("Unsupported image type: {$source}");
        }

        $newW = min($width, $w);
        $newH = (int) round($h * ($newW / $w));
        $dst  = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);

        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }
        imageinterlace($dst, true); // progressive JPEG: paints while downloading
        imagejpeg($dst, $target, 78);
    }
}
