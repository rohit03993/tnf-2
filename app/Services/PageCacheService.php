<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PageCacheService
{
    public static function enabled(): bool
    {
        if (app()->environment('local')) {
            return false;
        }

        $default = (bool) config('tnf.page_cache_enabled', true);

        return \App\Support\TnfSetting::bool('page_cache_enabled', $default);
    }

    public static function version(): int
    {
        return (int) Cache::get('page_cache.version', 1);
    }

    public static function bump(): void
    {
        Cache::increment('page_cache.version');
    }

    public static function key(Request $request): string
    {
        return 'page_cache.v'.self::version().'.a'.self::assetVersion().':'.sha1($request->fullUrl());
    }

    public static function assetVersion(): string
    {
        $parts = [];

        $manifest = public_path('build/manifest.json');

        if (is_file($manifest)) {
            $parts[] = (string) filemtime($manifest);
        }

        $views = base_path('resources/views');

        if (is_dir($views)) {
            $parts[] = (string) self::latestMtime($views, ['blade.php']);
        }

        return $parts === [] ? 'none' : sha1(implode('|', $parts));
    }

    /** @param  list<string>  $extensions */
    protected static function latestMtime(string $directory, array $extensions): int
    {
        $latest = 0;

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $extension = strtolower($file->getExtension());

            if (! in_array($extension, $extensions, true)) {
                continue;
            }

            $latest = max($latest, $file->getMTime());
        }

        return $latest;
    }

    public static function ttl(): int
    {
        $default = (int) config('tnf.page_cache_ttl', 300);

        return (int) \App\Support\TnfSetting::get('page_cache_ttl', $default);
    }
}
