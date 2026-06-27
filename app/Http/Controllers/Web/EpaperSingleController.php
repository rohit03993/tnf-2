<?php

namespace App\Http\Controllers\Web;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\EpaperEdition;
use App\Services\EpaperAccessService;
use App\Services\EpaperClipSignatureService;
use App\Services\EpaperViewerService;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EpaperSingleController extends Controller
{
    public function __invoke(EpaperEdition $edition, SeoService $seo): View|RedirectResponse
    {
        abort_unless(
            $edition->status === ContentStatus::Published
            && $edition->published_at
            && $edition->published_at <= now(),
            404,
        );

        $edition->load('featuredMedia');

        if (EpaperAccessService::gate(auth()->user(), $edition) === 'premium') {
            return view('epaper.premium-gate', [
                'edition' => $edition,
                'isGuest' => ! auth()->check(),
                'seo' => $seo->forEpaper($edition, request()),
            ]);
        }

        if (request()->boolean('tnf_clip') && ! EpaperClipSignatureService::verify($edition, request())) {
            abort(403, 'This clip link has expired or is invalid.');
        }

        return view('epaper.show', [
            'edition' => $edition,
            'config' => EpaperViewerService::config($edition, request()),
            'seo' => $seo->forEpaper($edition, request()),
        ]);
    }
}
