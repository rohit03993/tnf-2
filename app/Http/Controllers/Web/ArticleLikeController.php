<?php

namespace App\Http\Controllers\Web;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\ArticleReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleLikeController extends Controller
{
    public function __invoke(Article $article, Request $request, ArticleReadService $reads): JsonResponse
    {
        abort_unless(
            $article->status === ContentStatus::Published
            && $article->published_at
            && $article->published_at <= now(),
            404,
        );

        $result = $reads->toggleLike($article, $request);

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
