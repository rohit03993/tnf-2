<?php

namespace App\Http\Controllers\Web;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\EpaperEdition;
use App\Models\Video;
use App\Services\EpaperClipSignatureService;
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

        $imageUrl = EpaperViewerService::shareImageSourceUrl($edition);

        return $ogImages->serveOrGenerate('epaper', $edition->id, $imageUrl);
    }

    public function epaperClip(Request $request, EpaperEdition $edition, OgImageService $ogImages): Response
    {
        abort_unless($edition->status === ContentStatus::Published, 404);

        if (! EpaperClipSignatureService::hasValidClipParams($this->clipRequest($request))) {
            abort(404);
        }

        $pageIndex = max(0, $this->clipPage($request) - 1);
        $imageUrl = EpaperViewerService::shareImageSourceUrl($edition, $pageIndex);

        $crop = [
            'x' => (float) $this->clipQuery($request, 'cx', 0),
            'y' => (float) $this->clipQuery($request, 'cy', 0),
            'w' => (float) $this->clipQuery($request, 'cw', 0),
            'h' => (float) $this->clipQuery($request, 'ch', 0),
        ];

        return $ogImages->serveCropped($imageUrl, $crop, $edition->title);
    }

    protected function clipRequest(Request $request): Request
    {
        if ($request->has('tnf_pg') || $request->has('tnf_cw')) {
            return $request;
        }

        return Request::create('/', 'GET', [
            'tnf_pg' => $request->query('pg', 1),
            'tnf_cx' => $request->query('cx', 0),
            'tnf_cy' => $request->query('cy', 0),
            'tnf_cw' => $request->query('cw', 0),
            'tnf_ch' => $request->query('ch', 0),
        ]);
    }

    protected function clipPage(Request $request): int
    {
        return max(1, (int) $request->query('tnf_pg', $request->query('pg', 1)));
    }

    protected function clipQuery(Request $request, string $key, float $default): float
    {
        $tnfKey = 'tnf_'.$key;

        if ($request->query($tnfKey) !== null) {
            return (float) $request->query($tnfKey, $default);
        }

        return (float) $request->query($key, $default);
    }
}
