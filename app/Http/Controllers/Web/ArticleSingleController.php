<?php

namespace App\Http\Controllers\Web;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\ArticleReadService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleSingleController extends Controller
{
    public function __invoke(
        Article $article,
        Request $request,
        SeoService $seo,
        ArticleReadService $reads,
    ): Response {
        abort_unless(
            $article->status === ContentStatus::Published
            && $article->published_at
            && $article->published_at <= now(),
            404,
        );

        $article->load(['author', 'categories', 'featuredMedia', 'tags']);

        $counts = $reads->record($article, $request);
        $article->setAttribute('readers_count', $counts['readers_count']);
        $article->setAttribute('likes_count', $counts['likes_count']);
        $article->setAttribute('views_count', $counts['views_count']);

        $primaryCategory = $article->categories->first();

        $relatedArticles = Article::query()
            ->published()
            ->with('featuredMedia')
            ->when($primaryCategory, fn ($query) => $query->inCategory($primaryCategory->slug))
            ->where('id', '!=', $article->id)
            ->latest('published_at')
            ->limit(4)
            ->get();

        $response = response()->view('pages.articles.show', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
            'seo' => $seo->forArticle($article),
            'readRecorded' => true,
        ]);

        return $reads->attachReaderCookie($request, $response);
    }
}
