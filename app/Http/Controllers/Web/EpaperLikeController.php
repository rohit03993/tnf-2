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

class EpaperLikeController extends Controller
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

        $result = $reads->toggleLike($edition, $request);

        $response = response()->json([
            'liked' => $result['liked'],
            'likes_count' => $result['likes_count'],
            'likes_label' => ArticleReadService::formatCount($result['likes_count']),
            'readers_count' => $result['readers_count'],
            'readers_label' => ArticleReadService::formatCount($result['readers_count']),
        ]);

        return $reads->attachReaderCookie($request, $response);
    }
}
