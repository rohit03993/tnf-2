<?php

namespace App\Http\Controllers\Web;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\EpaperEdition;
use App\Services\EpaperClipCodeService;
use App\Services\EpaperReadService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EpaperClipShortController extends Controller
{
    public function __invoke(
        string $token,
        Request $request,
        SeoService $seo,
        EpaperReadService $reads,
    ): Response {
        $decoded = EpaperClipCodeService::decode($token);

        abort_unless($decoded !== null, 404);

        $edition = EpaperEdition::query()->findOrFail($decoded['edition_id']);

        abort_unless(
            $edition->status === ContentStatus::Published
            && $edition->published_at
            && $edition->published_at <= now(),
            404,
        );

        $request->query->add(EpaperClipCodeService::toQuery($decoded));

        return app(EpaperSingleController::class)($edition, $request, $seo, $reads);
    }
}
