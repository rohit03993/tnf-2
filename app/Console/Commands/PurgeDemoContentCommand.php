<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\EpaperEdition;
use App\Models\Media;
use App\Models\Video;
use App\Services\HomepageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurgeDemoContentCommand extends Command
{
    protected $signature = 'tnf:purge-demo-content {--force : Skip confirmation}';

    protected $description = 'Remove demo articles, videos, ePaper editions, and demo media files';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Delete all demo-* content from the database?')) {
            return self::SUCCESS;
        }

        $articles = Article::query()->where('slug', 'like', 'demo-%')->count();
        $videos = Video::query()->where('slug', 'like', 'demo-%')->count();
        $epapers = EpaperEdition::query()->where('slug', 'like', 'demo-%')->count();

        DB::transaction(function () use ($articles, $videos, $epapers): void {
            Article::query()->where('slug', 'like', 'demo-%')->delete();
            Video::query()->where('slug', 'like', 'demo-%')->delete();
            EpaperEdition::query()->where('slug', 'like', 'demo-%')->delete();

            $demoMedia = Media::query()->where('path', 'like', 'demo/%')->get();

            foreach ($demoMedia as $media) {
                if (Storage::disk($media->disk)->exists($media->path)) {
                    Storage::disk($media->disk)->delete($media->path);
                }

                $media->delete();
            }
        });

        app(HomepageService::class)->clearCache();

        $this->info("Removed demo content: {$articles} articles, {$videos} videos, {$epapers} ePaper editions.");

        return self::SUCCESS;
    }
}
