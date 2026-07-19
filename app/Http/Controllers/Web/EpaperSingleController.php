<?php

namespace App\Http\Controllers\Web;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\EpaperEdition;
use App\Services\EpaperAccessService;
use App\Services\EpaperClipSignatureService;
use App\Services\EpaperReadService;
use App\Services\EpaperViewerService;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EpaperSingleController extends Controller
{
    public function __invoke(
        EpaperEdition $edition,
        Request $request,
        SeoService $seo,
        EpaperReadService $reads,
    ): Response|RedirectResponse {
        abort_unless(
            $edition->status === ContentStatus::Published
            && $edition->published_at
            && $edition->published_at <= now(),
            404,
        );

        $edition->load('featuredMedia');

        if (EpaperAccessService::gate($request->user(), $edition) === 'premium') {
            return view('epaper.premium-gate', [
                'edition' => $edition,
                'isGuest' => ! $request->user(),
                'seo' => $seo->forEpaper($edition, $request),
            ]);
        }

        if ($request->boolean('tnf_clip') && ! EpaperClipSignatureService::verify($edition, $request)) {
            abort(403, 'This clip link has expired or is invalid.');
        }

        $clipMode = $request->boolean('tnf_clip');
        $readRecorded = false;

        if (! $clipMode) {
            $counts = $reads->record($edition, $request);
            $edition->setAttribute('readers_count', $counts['readers_count']);
            $edition->setAttribute('likes_count', $counts['likes_count']);
            $edition->setAttribute('views_count', $counts['views_count']);
            $readRecorded = true;
        }

        $config = EpaperViewerService::config($edition, $request);
        $config['readRecorded'] = $readRecorded;

        $response = response()->view('epaper.show', [
            'edition' => $edition,
            'config' => $config,
            'seo' => $seo->forEpaper($edition, $request),
        ]);

        return $readRecorded
            ? $reads->attachReaderCookie($request, $response)
            : $response;
    }
}
