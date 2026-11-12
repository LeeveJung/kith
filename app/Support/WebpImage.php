<?php

namespace App\Support;

use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Compresses uploaded images: converts them to WebP and scales them down
 * to a maximum width. Smaller images keep their size.
 */
final class WebpImage
{
    public const MAX_WIDTH = 1000;

    public const QUALITY = 80;

    /**
     * MIME types that can be read and converted.
     *
     * @var list<string>
     */
    public const ACCEPTED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /**
     * Converts the image contents to WebP, scaled down to at most the given width.
     *
     * @throws InvalidArgumentException if the contents are not a readable image
     */
    public static function encode(string $contents, int $maxWidth = self::MAX_WIDTH, int $quality = self::QUALITY): string
    {
        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            throw new InvalidArgumentException('The file is not a readable image.');
        }

        imagepalettetotruecolor($image);
        $image = self::orient($image, $contents);

        if (imagesx($image) > $maxWidth) {
            $image = imagescale($image, $maxWidth, -1, IMG_BICUBIC);
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, $quality);

        return (string) ob_get_clean();
    }

    /**
     * Converts the image contents and stores them as a new WebP file.
     *
     * @return string Path of the stored file on the disk
     */
    public static function store(string $contents, string $disk, string $directory): string
    {
        $path = trim($directory.'/'.Str::random(40).'.webp', '/');

        Storage::disk($disk)->put($path, self::encode($contents), 'public');

        return $path;
    }

    /**
     * Rotates JPEG photos (e.g. from phones) according to their EXIF orientation.
     */
    private static function orient(GdImage $image, string $contents): GdImage
    {
        if (! function_exists('exif_read_data') || ! str_starts_with($contents, "\xFF\xD8")) {
            return $image;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($contents));

        $angle = match ($exif['Orientation'] ?? null) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : imagerotate($image, $angle, 0);
    }
}
