<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Services\SeoService;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __invoke(Category $category, SeoService $seo): View
    {
        $articles = Article::query()
            ->published()
            ->with('featuredMedia')
            ->inCategory($category->slug)
            ->latest('published_at')
            ->paginate(12);

        return view('pages.archive', [
            'title' => $category->name,
            'heading' => $category->name,
            'description' => 'Latest news in '.$category->name,
            'articles' => $articles,
            'seo' => $seo->forCategory($category),
        ]);
    }
}
