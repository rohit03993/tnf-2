<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ContentCacheService
{
    /** Bust all public content caches after news, video, or ePaper changes. */
    public static function bust(): void
    {
        Cache::forget('homepage.data');
        Cache::forget('homepage.data.v2');
        Cache::forget('site.chrome.full');
        Cache::forget('site.chrome.auth');
        PageCacheService::bump();
    }
}
