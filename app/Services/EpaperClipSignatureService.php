<?php

namespace App\Services;

use App\Models\EpaperEdition;
use Illuminate\Http\Request;

class EpaperClipSignatureService
{
    public static function secret(): ?string
    {
        $secret = config('tnf.epaper_clip_og_secret');

        return filled($secret) ? (string) $secret : null;
    }

    public static function signingEnabled(): bool
    {
        return self::secret() !== null;
    }

    /** @param array{page: int, x: float, y: float, w: float, h: float} $clip */
    public static function sign(EpaperEdition $edition, array $clip, ?int $expires = null): array
    {
        $secret = self::secret();

        if (! $secret) {
            return [];
        }

        $expires ??= now()->addSeconds((int) config('tnf.clip_url_ttl', 86400))->timestamp;

        $payload = self::payload(
            $edition->id,
            (int) $clip['page'],
            (float) $clip['x'],
            (float) $clip['y'],
            (float) $clip['w'],
            (float) $clip['h'],
            $expires,
        );

        return [
            'tnf_exp' => $expires,
            'tnf_sig' => hash_hmac('sha256', $payload, $secret),
        ];
    }

    public static function verify(EpaperEdition $edition, Request $request): bool
    {
        if (! self::signingEnabled() || ! $request->boolean('tnf_clip')) {
            return true;
        }

        if (! self::hasValidClipParams($request)) {
            return false;
        }

        $signature = (string) $request->query('tnf_sig', '');
        $expires = (int) $request->query('tnf_exp', 0);

        // Coordinate-only share links (no signature) — permanent, like other e-paper clip URLs.
        if ($signature === '' || $expires <= 0) {
            return true;
        }

        if ($expires < now()->timestamp) {
            return false;
        }

        $payload = self::payload(
            $edition->id,
            (int) $request->query('tnf_pg', 1),
            (float) $request->query('tnf_cx', 0),
            (float) $request->query('tnf_cy', 0),
            (float) $request->query('tnf_cw', 0),
            (float) $request->query('tnf_ch', 0),
            $expires,
        );

        $expected = hash_hmac('sha256', $payload, self::secret());

        return hash_equals($expected, $signature);
    }

    public static function hasValidClipParams(Request $request): bool
    {
        $page = (int) $request->query('tnf_pg', 0);
        $width = (float) $request->query('tnf_cw', 0);
        $height = (float) $request->query('tnf_ch', 0);

        return $page >= 1
            && $width > 0
            && $height > 0
            && $width <= 1
            && $height <= 1;
    }

    protected static function payload(
        int $editionId,
        int $page,
        float $x,
        float $y,
        float $w,
        float $h,
        int $expires,
    ): string {
        return implode('|', [
            $editionId,
            $page,
            number_format($x, 4, '.', ''),
            number_format($y, 4, '.', ''),
            number_format($w, 4, '.', ''),
            number_format($h, 4, '.', ''),
            $expires,
        ]);
    }
}
