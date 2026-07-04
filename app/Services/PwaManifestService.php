<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class PwaManifestService
{
    public static function manifest(): array
    {
        return [
            'name' => config('app.name', 'TNF Today'),
            'short_name' => 'TNF Today',
            'id' => url('/'),
            'description' => 'Hindi news, videos, and digital ePaper from TNF Today.',
            'start_url' => url('/'),
            'scope' => url('/'),
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => '#FFFFFF',
            'theme_color' => '#BC1E38',
            'icons' => [
                [
                    'src' => self::iconUrl(192),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => self::iconUrl(512),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => self::iconUrl(512),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ];
    }

    public static function iconSourcePath(int $size): ?string
    {
        $pwaIcon = (string) Setting::get('pwa_icon', '');

        if (blank($pwaIcon) && Storage::disk('public')->exists(PwaIconService::CANONICAL_PATH)) {
            $pwaIcon = PwaIconService::CANONICAL_PATH;
        }

        if (filled($pwaIcon) && Storage::disk('public')->exists($pwaIcon)) {
            return $pwaIcon;
        }

        $favicon = (string) Setting::get('site_favicon', '');

        if (filled($favicon) && Storage::disk('public')->exists($favicon)) {
            return $favicon;
        }

        return null;
    }

    public static function iconVersion(): string
    {
        $path = self::iconSourcePath(512);

        if ($path !== null && Storage::disk('public')->exists($path)) {
            return (string) Storage::disk('public')->lastModified($path);
        }

        return 'default';
    }

    public static function iconUrl(int $size): string
    {
        return url(route('pwa.icon', ['size' => $size], false)).'?v='.self::iconVersion();
    }

    public static function fallbackIconPath(): string
    {
        return public_path('favicon.svg');
    }
}
