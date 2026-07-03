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
            'description' => 'Hindi news, videos, and digital ePaper from TNF Today.',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => '#FFFFFF',
            'theme_color' => '#BC1E38',
            'icons' => [
                [
                    'src' => route('pwa.icon', ['size' => 192]),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => route('pwa.icon', ['size' => 512]),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ];
    }

    public static function iconSourcePath(int $size): ?string
    {
        if ($size <= 192) {
            $favicon = (string) Setting::get('site_favicon', '');

            if (filled($favicon) && Storage::disk('public')->exists($favicon)) {
                return $favicon;
            }
        }

        $logo = (string) Setting::get('site_logo', BrandLogoService::CANONICAL_PATH);

        if (filled($logo) && Storage::disk('public')->exists($logo)) {
            return $logo;
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
