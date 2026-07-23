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
    public function serveCropped(?string $imageUrl, array $crop, ?string $title = null): Response
    {
        if (! $imageUrl) {
            return $this->serveDefault();
        }

        $jpeg = $this->buildBrandedClipJpeg($imageUrl, $crop, $title)
            ?? $this->buildJpeg($imageUrl, $crop, 'cover');

        if ($jpeg === null) {
            return $this->serveDefault();
        }

        return response($jpeg, 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Crop + brand card for shared ePaper clips (logo + edition title above the crop).
     *
     * @param  array{x: float, y: float, w: float, h: float}  $crop
     */
    public function buildBrandedClipJpeg(string $imageUrl, array $crop, ?string $title = null): ?string
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

        $targetWidth = 1200;
        $targetHeight = 630;
        $headerHeight = 126;
        $padding = 28;
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        $navy = imagecolorallocate($canvas, 15, 19, 32);
        $red = imagecolorallocate($canvas, 188, 30, 56);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $muted = imagecolorallocate($canvas, 180, 186, 198);
        $paper = imagecolorallocate($canvas, 232, 234, 237);

        imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $paper);
        imagefilledrectangle($canvas, 0, 0, $targetWidth, $headerHeight, $navy);
        imagefilledrectangle($canvas, 0, 0, $targetWidth, 6, $red);

        $logoMaxH = 64;
        $logoMaxW = 220;
        $textLeft = $padding;
        $logo = $this->loadBrandLogoResource();

        if ($logo !== null) {
            $logoW = imagesx($logo);
            $logoH = imagesy($logo);
            $scale = min($logoMaxW / max(1, $logoW), $logoMaxH / max(1, $logoH), 1);
            $drawW = max(1, (int) round($logoW * $scale));
            $drawH = max(1, (int) round($logoH * $scale));
            $logoX = $padding;
            $logoY = (int) (($headerHeight - $drawH) / 2) + 2;

            imagecopyresampled($canvas, $logo, $logoX, $logoY, 0, 0, $drawW, $drawH, $logoW, $logoH);
            imagedestroy($logo);
            $textLeft = $logoX + $drawW + 20;
        }

        $editionTitle = trim((string) $title);
        $eyebrow = 'SHARED NEWSPAPER CLIP';
        $this->drawOgText($canvas, $eyebrow, $textLeft, 38, 18, $muted, $targetWidth - $textLeft - $padding);
        $this->drawOgText(
            $canvas,
            $editionTitle !== '' ? $editionTitle : (string) config('app.name', 'TNF Today'),
            $textLeft,
            72,
            28,
            $white,
            $targetWidth - $textLeft - $padding,
        );

        $contentTop = $headerHeight + $padding;
        $contentWidth = $targetWidth - ($padding * 2);
        $contentHeight = $targetHeight - $contentTop - $padding;
        $srcRatio = $cropW / $cropH;
        $boxRatio = $contentWidth / $contentHeight;

        if ($srcRatio > $boxRatio) {
            $drawWidth = $contentWidth;
            $drawHeight = (int) round($contentWidth / $srcRatio);
        } else {
            $drawHeight = $contentHeight;
            $drawWidth = (int) round($contentHeight * $srcRatio);
        }

        $destX = (int) (($targetWidth - $drawWidth) / 2);
        $destY = $contentTop + (int) (($contentHeight - $drawHeight) / 2);

        $resized = imagecreatetruecolor($drawWidth, $drawHeight);
        $resizedWhite = imagecolorallocate($resized, 255, 255, 255);
        imagefill($resized, 0, 0, $resizedWhite);
        imagecopyresampled($resized, $cropped, 0, 0, 0, 0, $drawWidth, $drawHeight, $cropW, $cropH);
        imagecopy($canvas, $resized, $destX, $destY, 0, 0, $drawWidth, $drawHeight);

        ob_start();
        imagejpeg($canvas, null, 85);
        $jpeg = ob_get_clean() ?: null;

        imagedestroy($cropped);
        imagedestroy($resized);
        imagedestroy($canvas);

        return $jpeg;
    }

    /** @return \GdImage|resource|null */
    protected function loadBrandLogoResource()
    {
        $path = BrandLogoService::storedLogoPath();

        if (! filled($path) || ! Storage::disk('public')->exists($path)) {
            if ($path !== BrandLogoService::CANONICAL_PATH && Storage::disk('public')->exists(BrandLogoService::CANONICAL_PATH)) {
                $path = BrandLogoService::CANONICAL_PATH;
            } else {
                return null;
            }
        }

        $bytes = Storage::disk('public')->get($path);
        $logo = @imagecreatefromstring($bytes);

        return $logo === false ? null : $logo;
    }

    protected function resolveOgFontPath(): ?string
    {
        $candidates = [
            public_path('fonts/DejaVuSans.ttf'),
            public_path('fonts/NotoSans-Regular.ttf'),
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/segoeui.ttf',
            'C:/Windows/Fonts/Nirmala.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/noto/NotoSans-Regular.ttf',
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /** @param \GdImage|resource $canvas */
    protected function drawOgText($canvas, string $text, int $x, int $y, int $size, int $color, int $maxWidth): void
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        if ($text === '') {
            return;
        }

        $font = $this->resolveOgFontPath();

        if ($font && function_exists('imagettftext')) {
            $lines = $this->wrapOgText($text, $font, $size, $maxWidth);

            foreach (array_slice($lines, 0, 2) as $index => $line) {
                imagettftext($canvas, $size, 0, $x, $y + ($index * (int) round($size * 1.25)), $color, $font, $line);
            }

            return;
        }

        $safe = preg_replace('/[^\x20-\x7E]/', '', $text) ?: (string) config('app.name', 'TNF Today');
        $fontSize = $size >= 26 ? 5 : 3;
        $charWidth = imagefontwidth($fontSize);
        $maxChars = max(8, (int) floor($maxWidth / max(1, $charWidth)));
        $line = strlen($safe) > $maxChars ? rtrim(substr($safe, 0, $maxChars - 1)).'...' : $safe;
        imagestring($canvas, $fontSize, $x, $y - imagefontheight($fontSize), $line, $color);
    }

    /** @return list<string> */
    protected function wrapOgText(string $text, string $font, int $size, int $maxWidth): array
    {
        $words = preg_split('/\s+/u', $text) ?: [$text];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $trial = $current === '' ? $word : $current.' '.$word;
            $box = imagettfbbox($size, 0, $font, $trial);
            $width = $box ? (int) abs($box[2] - $box[0]) : strlen($trial) * $size;

            if ($width <= $maxWidth || $current === '') {
                $current = $trial;
            } else {
                $lines[] = $current;
                $current = $word;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines === [] ? [$text] : $lines;
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
