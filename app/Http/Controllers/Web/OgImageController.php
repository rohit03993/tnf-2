<?php

namespace App\Http\Controllers\Web;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\EpaperEdition;
use App\Models\Video;
use App\Services\EpaperViewerService;
use App\Services\OgImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OgImageController extends Controller
{
    public function default(OgImageService $ogImages): Response
    {
        return $ogImages->serveDefault();
    }

    public function article(Article $article, OgImageService $ogImages): Response|RedirectResponse
    {
        abort_unless($article->status === ContentStatus::Published, 404);

        $article->load('featuredMedia');

        return $ogImages->serveOrGenerate('article', $article->id, $article->featuredMedia?->url());
    }

    public function video(Video $video, OgImageService $ogImages): Response|RedirectResponse
    {
        abort_unless($video->status === ContentStatus::Published, 404);

        $video->load('featuredMedia');

        return $ogImages->serveOrGenerate('video', $video->id, $video->featuredMedia?->url());
    }

    public function epaperPage(EpaperEdition $edition, OgImageService $ogImages): Response
    {
        abort_unless($edition->status === ContentStatus::Published, 404);

        $edition->load('featuredMedia');
        $pages = EpaperViewerService::normalizePages($edition);
        $imageUrl = $pages[0]['url'] ?? $edition->featuredMedia?->url();

        return $ogImages->serveOrGenerate('epaper', $edition->id, $imageUrl);
    }

    public function epaperClip(Request $request, EpaperEdition $edition, OgImageService $ogImages): Response
    {
        abort_unless($edition->status === ContentStatus::Published, 404);
        abort_unless($ogImages->verifyClipSignature($edition, $request->query()), 403);

        $edition->load('featuredMedia');
        $pages = EpaperViewerService::normalizePages($edition);
        $pageIndex = max(0, (int) $request->query('pg', 1) - 1);
        $imageUrl = $pages[$pageIndex]['url'] ?? $edition->featuredMedia?->url();

        return $ogImages->serveOrGenerate('epaper_clip', $edition->id, $imageUrl);
    }
}
