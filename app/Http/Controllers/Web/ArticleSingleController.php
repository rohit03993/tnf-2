<?php

namespace App\Http\Controllers\Web;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\SeoService;
use Illuminate\View\View;

class ArticleSingleController extends Controller
{
    public function __invoke(Article $article, SeoService $seo): View
    {
        abort_unless(
            $article->status === ContentStatus::Published
            && $article->published_at
            && $article->published_at <= now(),
            404,
        );

        $article->load(['author', 'categories', 'featuredMedia', 'tags']);

        $primaryCategory = $article->categories->first();

        $relatedArticles = Article::query()
            ->published()
            ->with('featuredMedia')
            ->when($primaryCategory, fn ($query) => $query->inCategory($primaryCategory->slug))
            ->where('id', '!=', $article->id)
            ->latest('published_at')
            ->limit(4)
            ->get();

        return view('pages.articles.show', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
            'seo' => $seo->forArticle($article),
        ]);
    }
}
