<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Jobs\GenerateOgImageJob;
use App\Jobs\SendPushNotificationJob;
use App\Jobs\SendWhatsAppBroadcastJob;
use App\Models\Article;
use App\Models\EpaperEdition;
use App\Models\Video;
use App\Support\FrontendUrl;
use App\Support\TnfSetting;

class ContentPublishService
{
    protected static bool $whatsAppRequested = false;

    public static function requestWhatsAppBroadcast(bool $yes = true): void
    {
        static::$whatsAppRequested = $yes;
    }

    public static function handlePublishedArticle(Article $article): void
    {
        if ($article->status !== ContentStatus::Published || ! $article->wasChanged('status')) {
            return;
        }

        GenerateOgImageJob::dispatchSync('article', $article->id);

        $url = FrontendUrl::route('article.show', $article);

        if (TnfSetting::bool('push_on_news', true)) {
            SendPushNotificationJob::dispatchSync(
                $article->title,
                $article->excerpt ?: 'New story on TNF Today',
                $url,
            );
        }

        if (static::$whatsAppRequested || TnfSetting::bool('whatsapp_on_news', false)) {
            SendWhatsAppBroadcastJob::dispatch($article->title, $url, 'news');
        }

        static::$whatsAppRequested = false;
    }

    public static function handlePublishedVideo(Video $video): void
    {
        if ($video->status !== ContentStatus::Published || ! $video->wasChanged('status')) {
            return;
        }

        GenerateOgImageJob::dispatchSync('video', $video->id);

        if (! TnfSetting::bool('push_on_videos', true)) {
            return;
        }

        SendPushNotificationJob::dispatchSync(
            $video->title,
            $video->excerpt ?: 'New video on TNF Today',
            FrontendUrl::route('videos.show', $video->slug),
        );
    }

    public static function handlePublishedEpaper(EpaperEdition $edition): void
    {
        if ($edition->status !== ContentStatus::Published || ! $edition->wasChanged('status')) {
            return;
        }

        GenerateOgImageJob::dispatchSync('epaper', $edition->id);

        $url = FrontendUrl::route('epaper.show', $edition->slug);

        if (TnfSetting::bool('push_on_epaper', true)) {
            SendPushNotificationJob::dispatchSync(
                $edition->title,
                $edition->excerpt ?: 'New ePaper edition available',
                $url,
            );
        }

        if (static::$whatsAppRequested || TnfSetting::bool('whatsapp_on_epaper', false)) {
            SendWhatsAppBroadcastJob::dispatch($edition->title, $url, 'epaper');
        }

        static::$whatsAppRequested = false;
    }
}
