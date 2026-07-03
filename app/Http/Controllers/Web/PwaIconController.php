<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\PwaManifestService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PwaIconController extends Controller
{
    public function __invoke(int $size): Response
    {
        if (! in_array($size, [192, 512], true)) {
            abort(404);
        }

        $png = $this->renderIcon($size);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    protected function renderIcon(int $size): string
    {
        $sourcePath = PwaManifestService::iconSourcePath($size);

        if ($sourcePath !== null) {
            $bytes = Storage::disk('public')->get($sourcePath);
            $image = @imagecreatefromstring($bytes);

            if ($image !== false) {
                $png = $this->resizeSquare($image, $size);
                imagedestroy($image);

                if ($png !== null) {
                    return $png;
                }
            }
        }

        $fallback = PwaManifestService::fallbackIconPath();

        if (is_file($fallback)) {
            $svg = file_get_contents($fallback);

            if (is_string($svg) && extension_loaded('gd')) {
                $image = $this->rasterizeSvgPlaceholder($size);

                if ($image !== null) {
                    return $image;
                }
            }
        }

        return $this->solidPlaceholder($size);
    }

    protected function resizeSquare(\GdImage $source, int $size): ?string
    {
        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);

        if ($srcWidth < 1 || $srcHeight < 1) {
            return null;
        }

        $scale = min($size / $srcWidth, $size / $srcHeight);
        $targetWidth = max(1, (int) round($srcWidth * $scale));
        $targetHeight = max(1, (int) round($srcHeight * $scale));

        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefill($canvas, 0, 0, $transparent);
        imagealphablending($canvas, true);

        $offsetX = (int) floor(($size - $targetWidth) / 2);
        $offsetY = (int) floor(($size - $targetHeight) / 2);

        imagecopyresampled(
            $canvas,
            $source,
            $offsetX,
            $offsetY,
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
        imagedestroy($canvas);

        return $png;
    }

    protected function rasterizeSvgPlaceholder(int $size): ?string
    {
        $canvas = imagecreatetruecolor($size, $size);
        $red = imagecolorallocate($canvas, 188, 30, 56);
        imagefilledrectangle($canvas, 0, 0, $size, $size, $red);

        $white = imagecolorallocate($canvas, 255, 255, 255);
        $font = 5;
        $text = 'TNF';
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        imagestring($canvas, $font, (int) (($size - $textWidth) / 2), (int) (($size - $textHeight) / 2), $text, $white);

        ob_start();
        imagepng($canvas, null, 8);
        $png = ob_get_clean() ?: null;
        imagedestroy($canvas);

        return $png;
    }

    protected function solidPlaceholder(int $size): string
    {
        $png = $this->rasterizeSvgPlaceholder($size);

        return $png ?? '';
    }
}
