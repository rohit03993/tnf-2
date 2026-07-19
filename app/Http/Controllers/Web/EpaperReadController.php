<?php

namespace App\Http\Controllers\Web;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\EpaperEdition;
use App\Services\ArticleReadService;
use App\Services\EpaperAccessService;
use App\Services\EpaperReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EpaperReadController extends Controller
{
    public function __invoke(EpaperEdition $edition, Request $request, EpaperReadService $reads): JsonResponse
    {
        abort_unless(
            $edition->status === ContentStatus::Published
            && $edition->published_at
            && $edition->published_at <= now(),
            404,
        );

        abort_unless(
            EpaperAccessService::gate($request->user(), $edition) !== 'premium',
            403,
        );

        $result = $reads->record($edition, $request);

        $response = response()->json([
            'readers_count' => $result['readers_count'],
            'views_count' => $result['views_count'],
            'likes_count' => $result['likes_count'],
            'liked' => $result['liked'],
            'readers_label' => ArticleReadService::formatCount($result['readers_count']),
            'likes_label' => ArticleReadService::formatCount($result['likes_count']),
        ]);

        return $reads->attachReaderCookie($request, $response);
    }
}
