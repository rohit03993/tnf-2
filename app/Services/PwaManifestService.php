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
                    'src' => url(route('pwa.icon', ['size' => 192], false)),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => url(route('pwa.icon', ['size' => 512], false)),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => url(route('pwa.icon', ['size' => 512], false)),
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

        if (filled($pwaIcon) && Storage::disk('public')->exists($pwaIcon)) {
            return $pwaIcon;
        }

        $favicon = (string) Setting::get('site_favicon', '');

        if (filled($favicon) && Storage::disk('public')->exists($favicon)) {
            return $favicon;
        }

        return null;
    }

    public static function fallbackIconPath(): string
    {
        return public_path('favicon.svg');
    }
}
