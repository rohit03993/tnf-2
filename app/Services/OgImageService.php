<?php

namespace App\Services;

use App\Models\Article;
use App\Models\EpaperEdition;
use App\Models\OgImage;
use App\Models\Video;
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

    public function generateForEntity(string $type, int $id, ?string $imageUrl): ?OgImage
    {
        if (! $imageUrl) {
            return null;
        }

        $jpeg = $this->buildJpeg($imageUrl);

        if ($jpeg === null) {
            return null;
        }

        $path = "og/{$type}-{$id}.jpg";
        Storage::disk('public')->put($path, $jpeg);

        return OgImage::query()->updateOrCreate(
            ['entity_type' => $type, 'entity_id' => $id],
            ['path' => $path, 'signature_hash' => null],
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

    public function serveOrGenerate(string $type, int $id, ?string $imageUrl): Response
    {
        $og = OgImage::query()->where('entity_type', $type)->where('entity_id', $id)->first();

        if (! $og && $imageUrl) {
            $og = $this->generateForEntity($type, $id, $imageUrl);
        }

        abort_unless($og, 404);

        return $this->serve($og);
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

    protected function buildJpeg(string $imageUrl): ?string
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

        $targetWidth = 1200;
        $targetHeight = 630;
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);
        $srcRatio = $srcWidth / $srcHeight;
        $targetRatio = $targetWidth / $targetHeight;

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
        $y = (int) (($newHeight - $targetHeight) / 2);
        imagecopy($canvas, $resized, 0, 0, $x, $y, $targetWidth, $targetHeight);

        ob_start();
        imagejpeg($canvas, null, 82);
        $jpeg = ob_get_clean() ?: null;

        imagedestroy($source);
        imagedestroy($resized);
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
