<?php

namespace App\Support;

class Embed
{
    public static function youtubeVideoId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{6,})/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public static function isInstagram(?string $url): bool
    {
        if (! $url) {
            return false;
        }

        return (bool) preg_match('/(?:instagram\.com|instagr\.am)\/(?:reel|reels|p|tv)\//i', $url);
    }

    public static function isYoutube(?string $url): bool
    {
        return self::youtubeVideoId($url) !== null
            || ($url && str_contains($url, 'youtube.com/embed/'));
    }

    /** Auto thumbnail for YouTube / Shorts / Reels-style links (not uploaded media). */
    public static function previewImageUrl(?string $url): ?string
    {
        $videoId = self::youtubeVideoId($url);

        if ($videoId) {
            return 'https://img.youtube.com/vi/'.$videoId.'/hqdefault.jpg';
        }

        return null;
    }

    public static function youtubeIframeSrc(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $videoId = self::youtubeVideoId($url);

        if ($videoId) {
            return self::youtubeEmbedUrl($videoId);
        }

        if (str_contains($url, 'youtube.com/embed/') || str_contains($url, 'youtube-nocookie.com/embed/')) {
            return self::normalizeYoutubeEmbedUrl($url);
        }

        return null;
    }

    public static function youtubeEmbedUrl(string $videoId): string
    {
        $params = http_build_query([
            'rel' => '0',
            'modestbranding' => '1',
            'playsinline' => '1',
            'origin' => rtrim((string) config('app.url'), '/'),
        ]);

        return 'https://www.youtube-nocookie.com/embed/'.$videoId.'?'.$params;
    }

    protected static function normalizeYoutubeEmbedUrl(string $url): string
    {
        $videoId = self::youtubeVideoId($url);

        if ($videoId) {
            return self::youtubeEmbedUrl($videoId);
        }

        return $url;
    }
}
