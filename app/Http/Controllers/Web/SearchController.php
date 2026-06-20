<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\EpaperEdition;
use App\Models\Video;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request, SeoService $seo): View
    {
        $query = trim((string) $request->query('q', ''));
        $articles = null;
        $videos = collect();
        $epapers = collect();

        if (strlen($query) >= 2) {
            $articles = Article::query()
                ->published()
                ->with('featuredMedia')
                ->where(function ($builder) use ($query) {
                    $builder->where('title', 'like', "%{$query}%")
                        ->orWhere('excerpt', 'like', "%{$query}%")
                        ->orWhere('content', 'like', "%{$query}%");
                })
                ->latest('published_at')
                ->paginate(12)
                ->withQueryString();

            $videos = Video::query()
                ->published()
                ->with('featuredMedia')
                ->where(function ($builder) use ($query) {
                    $builder->where('title', 'like', "%{$query}%")
                        ->orWhere('excerpt', 'like', "%{$query}%");
                })
                ->latest('published_at')
                ->limit(6)
                ->get();

            $epapers = EpaperEdition::query()
                ->published()
                ->with('featuredMedia')
                ->where(function ($builder) use ($query) {
                    $builder->where('title', 'like', "%{$query}%")
                        ->orWhere('excerpt', 'like', "%{$query}%")
                        ->orWhere('content', 'like', "%{$query}%");
                })
                ->latest('published_at')
                ->limit(6)
                ->get();
        }

        return view('pages.search', [
            'query' => $query,
            'articles' => $articles ?? collect(),
            'videos' => $videos,
            'epapers' => $epapers,
            'seo' => $seo->forSearch($query ?: null),
        ]);
    }
}
