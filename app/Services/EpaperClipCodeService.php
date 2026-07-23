<?php

namespace App\Services;

class EpaperClipCodeService
{
    public const VERSION = 1;

    /**
     * Compact share token for clip coordinates.
     *
     * @param  array{page: int, x: float, y: float, w: float, h: float}  $clip
     */
    public static function encode(int $editionId, array $clip): string
    {
        $page = max(1, min(65535, (int) ($clip['page'] ?? 1)));
        $x = self::toUnits((float) ($clip['x'] ?? 0));
        $y = self::toUnits((float) ($clip['y'] ?? 0));
        $w = self::toUnits((float) ($clip['w'] ?? 0));
        $h = self::toUnits((float) ($clip['h'] ?? 0));

        $payload = pack('CNN', self::VERSION, $editionId, $page).pack('n*', $x, $y, $w, $h);
        $signature = substr(hash_hmac('sha256', $payload, self::signingKey(), true), 0, 8);

        return self::base64UrlEncode($payload.$signature);
    }

    /**
     * @return array{edition_id: int, page: int, x: float, y: float, w: float, h: float}|null
     */
    public static function decode(string $token): ?array
    {
        $raw = self::base64UrlDecode($token);

        // version(1) + edition(4) + page(4) + x/y/w/h(8) + hmac(8) = 25
        if ($raw === null || strlen($raw) !== 25) {
            return null;
        }

        $payload = substr($raw, 0, 17);
        $signature = substr($raw, 17, 8);
        $expected = substr(hash_hmac('sha256', $payload, self::signingKey(), true), 0, 8);

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $parts = unpack('Cversion/Nedition/Npage/nx/ny/nw/nh', $payload);

        if (! is_array($parts) || (int) $parts['version'] !== self::VERSION) {
            return null;
        }

        $page = (int) $parts['page'];
        $x = self::fromUnits((int) $parts['x']);
        $y = self::fromUnits((int) $parts['y']);
        $w = self::fromUnits((int) $parts['w']);
        $h = self::fromUnits((int) $parts['h']);

        if ($page < 1 || $w < 0.01 || $h < 0.01 || $w > 1 || $h > 1 || $x < 0 || $y < 0 || ($x + $w) > 1.001 || ($y + $h) > 1.001) {
            return null;
        }

        return [
            'edition_id' => (int) $parts['edition'],
            'page' => $page,
            'x' => $x,
            'y' => $y,
            'w' => $w,
            'h' => $h,
        ];
    }

    /** @return array<string, string|int> */
    public static function toQuery(array $decoded): array
    {
        return [
            'tnf_clip' => '1',
            'tnf_pg' => $decoded['page'],
            'tnf_cx' => number_format($decoded['x'], 4, '.', ''),
            'tnf_cy' => number_format($decoded['y'], 4, '.', ''),
            'tnf_cw' => number_format($decoded['w'], 4, '.', ''),
            'tnf_ch' => number_format($decoded['h'], 4, '.', ''),
        ];
    }

    protected static function signingKey(): string
    {
        // Permanent share tokens: bind to app key so rotating the OG clip secret
        // does not invalidate existing short links.
        return (string) config('app.key');
    }

    protected static function toUnits(float $value): int
    {
        return (int) max(0, min(10000, (int) round($value * 10000)));
    }

    protected static function fromUnits(int $units): float
    {
        return max(0, min(1, $units / 10000));
    }

    protected static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected static function base64UrlDecode(string $value): ?string
    {
        $remainder = strlen($value) % 4;

        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return is_string($decoded) ? $decoded : null;
    }
}
