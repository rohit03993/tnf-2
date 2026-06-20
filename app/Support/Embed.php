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
            return 'https://www.youtube.com/embed/'.$videoId;
        }

        if (str_contains($url, 'youtube.com/embed/')) {
            return $url;
        }

        return $url;
    }
}
