<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BrandLogoService
{
    public const CANONICAL_PATH = 'settings/brand/logo.png';

    public const MAX_WIDTH = 1400;

    public const MAX_HEIGHT = 360;

    public static function url(?string $path = null): ?string
    {
        $path ??= self::storedLogoPath();

        if (! filled($path) || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return asset('storage/'.$path);
    }

    public static function storedLogoPath(): string
    {
        $stored = \App\Models\Setting::get('site_logo', '');

        if (is_array($stored)) {
            $stored = $stored[0] ?? $stored['path'] ?? '';
        }

        return is_string($stored) ? $stored : '';
    }

    public static function process(string $disk, string $sourcePath): string
    {
        if (! extension_loaded('gd')) {
            return self::passthrough($disk, $sourcePath);
        }

        $storage = Storage::disk($disk);

        if (! $storage->exists($sourcePath)) {
            throw new RuntimeException('Uploaded logo file was not found.');
        }

        $bytes = $storage->get($sourcePath);
        $source = @imagecreatefromstring($bytes);

        if ($source === false) {
            throw new RuntimeException('The uploaded file is not a valid image.');
        }

        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);

        if ($srcWidth < 1 || $srcHeight < 1) {
            imagedestroy($source);

            throw new RuntimeException('The uploaded image has invalid dimensions.');
        }

        $scale = min(self::MAX_WIDTH / $srcWidth, self::MAX_HEIGHT / $srcHeight, 1);
        $targetWidth = max(1, (int) round($srcWidth * $scale));
        $targetHeight = max(1, (int) round($srcHeight * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefill($canvas, 0, 0, $transparent);
        imagealphablending($canvas, true);

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $srcWidth,
            $srcHeight,
        );

        ob_start();
        imagepng($canvas, null, 8);
        $png = ob_get_clean() ?: null;

        imagedestroy($source);
        imagedestroy($canvas);

        if ($png === null) {
            throw new RuntimeException('Could not optimize the uploaded logo.');
        }

        $storage->put(self::CANONICAL_PATH, $png);

        if ($sourcePath !== self::CANONICAL_PATH) {
            $storage->delete($sourcePath);
        }

        return self::CANONICAL_PATH;
    }

    protected static function passthrough(string $disk, string $sourcePath): string
    {
        $storage = Storage::disk($disk);

        if (! $storage->exists($sourcePath)) {
            throw new RuntimeException('Uploaded logo file was not found.');
        }

        if ($sourcePath === self::CANONICAL_PATH) {
            return $sourcePath;
        }

        $storage->put(self::CANONICAL_PATH, $storage->get($sourcePath));
        $storage->delete($sourcePath);

        return self::CANONICAL_PATH;
    }
}
