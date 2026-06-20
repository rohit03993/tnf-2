<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Jobs\GenerateOgImageJob;
use App\Jobs\SendPushNotificationJob;
use App\Models\Article;
use App\Models\EpaperEdition;
use App\Models\Video;
use App\Support\FrontendUrl;
use App\Support\TnfSetting;

class ContentPublishService
{
    public static function handlePublishedArticle(Article $article): void
    {
        if ($article->status !== ContentStatus::Published || ! $article->wasChanged('status')) {
            return;
        }

        GenerateOgImageJob::dispatchSync('article', $article->id);

        if (! TnfSetting::bool('push_on_news', true)) {
            return;
        }

        SendPushNotificationJob::dispatchSync(
            $article->title,
            $article->excerpt ?: 'New story on TNF Today',
            FrontendUrl::route('article.show', $article->slug),
        );
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

        if (! TnfSetting::bool('push_on_epaper', true)) {
            return;
        }

        SendPushNotificationJob::dispatchSync(
            $edition->title,
            $edition->excerpt ?: 'New ePaper edition available',
            FrontendUrl::route('epaper.show', $edition->slug),
        );
    }
}
