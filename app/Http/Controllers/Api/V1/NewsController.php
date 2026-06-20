<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Support\Api\WpContentSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $articles = Article::query()
            ->published()
            ->with('featuredMedia')
            ->latest('published_at')
            ->paginate((int) $request->query('per_page', 15));

        return response()->json([
            'data' => $articles->getCollection()->map(fn (Article $article) => WpContentSerializer::article($article))->values(),
            'meta' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
            ],
        ]);
    }

    public function show(Article $article): JsonResponse
    {
        abort_unless(
            $article->status === ContentStatus::Published
            && $article->published_at
            && $article->published_at <= now(),
            404,
        );

        return response()->json([
            'data' => WpContentSerializer::article($article->load('featuredMedia')),
        ]);
    }
}
