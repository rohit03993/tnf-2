<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\EpaperEdition;
use App\Models\Video;
use App\Services\EpaperViewerService;
use App\Services\OgImageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateOgImageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $entityType,
        public int $entityId,
    ) {}

    public function handle(OgImageService $ogImages): void
    {
        match ($this->entityType) {
            'article' => $this->forArticle($ogImages),
            'video' => $this->forVideo($ogImages),
            'epaper' => $this->forEpaper($ogImages),
            default => null,
        };
    }

    protected function forArticle(OgImageService $ogImages): void
    {
        $article = Article::query()->with('featuredMedia')->find($this->entityId);

        if ($article) {
            $ogImages->generateForEntity('article', $article->id, $article->featuredMedia?->url());
        }
    }

    protected function forVideo(OgImageService $ogImages): void
    {
        $video = Video::query()->with('featuredMedia')->find($this->entityId);

        if ($video) {
            $ogImages->generateForEntity('video', $video->id, $video->featuredMedia?->url());
        }
    }

    protected function forEpaper(OgImageService $ogImages): void
    {
        $edition = EpaperEdition::query()->with('featuredMedia')->find($this->entityId);

        if (! $edition) {
            return;
        }

        $imageUrl = EpaperViewerService::shareImageSourceUrl($edition);

        $ogImages->generateForEntity('epaper', $edition->id, $imageUrl, 'cover-top');
    }
}
