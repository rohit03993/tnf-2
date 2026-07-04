<?php

namespace App\Http\Controllers\Web;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\RedirectResponse;

class ArticleSlugRedirectController extends Controller
{
    public function __invoke(string $slug): RedirectResponse
    {
        $article = Article::query()->where('slug', $slug)->firstOrFail();

        abort_unless(
            $article->status === ContentStatus::Published
            && $article->published_at
            && $article->published_at <= now(),
            404,
        );

        return redirect()->route('article.show', $article, 301);
    }
}
