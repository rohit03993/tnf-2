<?php

namespace App\Services;

use App\Models\Article;
use App\Models\EpaperEdition;
use App\Models\OgImage;
use App\Models\Video;
use App\Support\FrontendUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OgImageService
{
    public function urlForArticle(Article $article): string
    {
        return route('og.article', $article);
    }

    public function urlForVideo(Video $video): string
    {
        return route('og.video', $video);
    }

    public function urlForEpaperPage(EpaperEdition $edition): string
    {
        return route('og.epaper.page', $edition);
    }

    public function generateForEntity(string $type, int $id, ?string $imageUrl, string $fit = 'cover'): ?OgImage
    {
        if (! $imageUrl) {
            return null;
        }

        $jpeg = $this->buildJpeg($imageUrl, null, $fit);

        if ($jpeg === null) {
            return null;
        }

        $path = "og/{$type}-{$id}.jpg";
        Storage::disk('public')->put($path, $jpeg);

        return OgImage::query()->updateOrCreate(
            ['entity_type' => $type, 'entity_id' => $id],
            ['path' => $path, 'signature_hash' => $fit],
        );
    }

    public function serve(OgImage $ogImage): Response
    {
        $disk = Storage::disk('public');

        abort_unless($disk->exists($ogImage->path), 404);

        return response($disk->get($ogImage->path), 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function serveOrGenerate(string $type, int $id, ?string $imageUrl): Response|RedirectResponse
    {
        $fit = $type === 'epaper' ? 'cover-top' : 'cover';
        $og = OgImage::query()->where('entity_type', $type)->where('entity_id', $id)->first();

        if ($og && $og->signature_hash !== $fit) {
            Storage::disk('public')->delete($og->path);
            $og->delete();
            $og = null;
        }

        if (! $og && $imageUrl) {
            $og = $this->generateForEntity($type, $id, $imageUrl, $fit);
        }

        if (! $og && $imageUrl) {
            return redirect(FrontendUrl::to($imageUrl));
        }

        if (! $og) {
            return $this->serveDefault();
        }

        return $this->serve($og);
    }

    /** @param array{x: float, y: float, w: float, h: float} $crop */
    public function serveCropped(?string $imageUrl, array $crop): Response
    {
        if (! $imageUrl) {
            return $this->serveDefault();
        }

        $jpeg = $this->buildJpeg($imageUrl, $crop, 'cover');

        if ($jpeg === null) {
            return $this->serveDefault();
        }

        return response($jpeg, 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function serveDefault(): Response
    {
        $path = 'og/default.jpg';
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            $jpeg = $this->buildDefaultBrandedJpeg();

            if ($jpeg === null) {
                abort(503, 'OG image unavailable');
            }

            $disk->put($path, $jpeg);
        }

        return response($disk->get($path), 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function verifyClipSignature(EpaperEdition $edition, array $params): bool
    {
        $secret = config('tnf.epaper_clip_og_secret');

        if (! $secret) {
            return false;
        }

        $expires = (int) ($params['expires'] ?? 0);

        if ($expires < now()->timestamp) {
            return false;
        }

        $payload = implode('|', [
            $edition->id,
            $params['cx'] ?? '',
            $params['cy'] ?? '',
            $params['cw'] ?? '',
            $params['ch'] ?? '',
            $expires,
        ]);

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, (string) ($params['signature'] ?? ''));
    }

    protected function buildJpeg(string $imageUrl, ?array $crop = null, string $fit = 'cover'): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $bytes = $this->loadImageBytes($imageUrl);

        if ($bytes === null) {
            return null;
        }

        $source = @imagecreatefromstring($bytes);

        if ($source === false) {
            return null;
        }

        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);

        if ($crop !== null) {
            $cropX = (int) round(max(0, min(1, (float) ($crop['x'] ?? 0))) * $srcWidth);
            $cropY = (int) round(max(0, min(1, (float) ($crop['y'] ?? 0))) * $srcHeight);
            $cropW = (int) round(max(0.01, min(1, (float) ($crop['w'] ?? 1))) * $srcWidth);
            $cropH = (int) round(max(0.01, min(1, (float) ($crop['h'] ?? 1))) * $srcHeight);

            $cropW = min($cropW, $srcWidth - $cropX);
            $cropH = min($cropH, $srcHeight - $cropY);

            if ($cropW < 1 || $cropH < 1) {
                imagedestroy($source);

                return null;
            }

            $cropped = imagecreatetruecolor($cropW, $cropH);
            imagecopy($cropped, $source, 0, 0, $cropX, $cropY, $cropW, $cropH);
            imagedestroy($source);
            $source = $cropped;
            $srcWidth = $cropW;
            $srcHeight = $cropH;
        }

        $targetWidth = 1200;
        $targetHeight = 630;
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        $srcRatio = $srcWidth / $srcHeight;
        $targetRatio = $targetWidth / $targetHeight;

        if ($fit === 'contain') {
            if ($srcRatio > $targetRatio) {
                $newWidth = $targetWidth;
                $newHeight = (int) round($targetWidth / $srcRatio);
            } else {
                $newHeight = $targetHeight;
                $newWidth = (int) round($targetHeight * $srcRatio);
            }

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

            $destX = (int) (($targetWidth - $newWidth) / 2);
            $destY = (int) (($targetHeight - $newHeight) / 2);
            imagecopy($canvas, $resized, $destX, $destY, 0, 0, $newWidth, $newHeight);
        } else {
            // cover / cover-top: fill canvas (no letterboxing); crop overflow.
            if ($srcRatio > $targetRatio) {
                $newHeight = $targetHeight;
                $newWidth = (int) ($targetHeight * $srcRatio);
            } else {
                $newWidth = $targetWidth;
                $newHeight = (int) ($targetWidth / $srcRatio);
            }

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

            $x = (int) (($newWidth - $targetWidth) / 2);
            // cover-top keeps the masthead (top of the page); cover centers.
            $y = $fit === 'cover-top' ? 0 : (int) (($newHeight - $targetHeight) / 2);
            imagecopy($canvas, $resized, 0, 0, $x, $y, $targetWidth, $targetHeight);
        }

        ob_start();
        imagejpeg($canvas, null, 82);
        $jpeg = ob_get_clean() ?: null;

        imagedestroy($source);
        imagedestroy($resized);
        imagedestroy($canvas);

        return $jpeg;
    }

    protected function buildDefaultBrandedJpeg(): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $width = 1200;
        $height = 630;
        $canvas = imagecreatetruecolor($width, $height);

        $navy = imagecolorallocate($canvas, 15, 19, 32);
        $red = imagecolorallocate($canvas, 188, 30, 56);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $muted = imagecolorallocate($canvas, 180, 186, 198);

        imagefilledrectangle($canvas, 0, 0, $width, $height, $navy);
        imagefilledrectangle($canvas, 0, 0, $width, 8, $red);

        $siteName = config('app.name', 'TNF Today');
        $titleSize = 5;
        $subtitleSize = 3;
        $titleWidth = imagefontwidth($titleSize) * strlen($siteName);
        $subtitle = 'Latest Hindi News';
        $subtitleWidth = imagefontwidth($subtitleSize) * strlen($subtitle);

        imagestring(
            $canvas,
            $titleSize,
            (int) (($width - $titleWidth) / 2),
            (int) (($height / 2) - 24),
            $siteName,
            $white,
        );
        imagestring(
            $canvas,
            $subtitleSize,
            (int) (($width - $subtitleWidth) / 2),
            (int) (($height / 2) + 8),
            $subtitle,
            $muted,
        );

        ob_start();
        imagejpeg($canvas, null, 85);
        $jpeg = ob_get_clean() ?: null;

        imagedestroy($canvas);

        return $jpeg;
    }

    protected function loadImageBytes(string $imageUrl): ?string
    {
        if (str_contains($imageUrl, '/storage/')) {
            $path = ltrim(Str::after($imageUrl, '/storage/'), '/');

            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->get($path);
            }
        }

        try {
            $response = Http::timeout(15)->get($imageUrl);

            return $response->successful() ? $response->body() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
