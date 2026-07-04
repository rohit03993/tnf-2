<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\EpaperEdition;
use App\Models\Tag;
use App\Models\Video;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'lastmod' => now()->toAtomString(), 'priority' => '1.0'],
            ['loc' => route('videos.index'), 'lastmod' => now()->toAtomString(), 'priority' => '0.8'],
            ['loc' => route('epaper.index'), 'lastmod' => now()->toAtomString(), 'priority' => '0.8'],
            ['loc' => route('search'), 'lastmod' => now()->toAtomString(), 'priority' => '0.5'],
            ['loc' => route('page.about'), 'lastmod' => now()->toAtomString(), 'priority' => '0.4'],
            ['loc' => route('page.contact'), 'lastmod' => now()->toAtomString(), 'priority' => '0.4'],
            ['loc' => route('page.privacy'), 'lastmod' => now()->toAtomString(), 'priority' => '0.3'],
            ['loc' => route('page.terms'), 'lastmod' => now()->toAtomString(), 'priority' => '0.3'],
        ]);

        Category::query()->orderBy('name')->get()->each(function (Category $category) use ($urls): void {
            $urls->push([
                'loc' => route('category.show', $category->slug),
                'lastmod' => $category->updated_at?->toAtomString() ?? now()->toAtomString(),
                'priority' => '0.7',
            ]);
        });

        Tag::query()->orderBy('name')->get()->each(function (Tag $tag) use ($urls): void {
            $urls->push([
                'loc' => route('tag.show', $tag->slug),
                'lastmod' => $tag->updated_at?->toAtomString() ?? now()->toAtomString(),
                'priority' => '0.6',
            ]);
        });

        Article::query()->published()->latest('published_at')->get()->each(function (Article $article) use ($urls): void {
            $urls->push([
                'loc' => route('article.show', $article),
                'lastmod' => $article->updated_at?->toAtomString() ?? $article->published_at?->toAtomString(),
                'priority' => '0.9',
            ]);
        });

        Video::query()->published()->latest('published_at')->get()->each(function (Video $video) use ($urls): void {
            $urls->push([
                'loc' => route('videos.show', $video->slug),
                'lastmod' => $video->updated_at?->toAtomString() ?? $video->published_at?->toAtomString(),
                'priority' => '0.8',
            ]);
        });

        EpaperEdition::query()->published()->latest('published_at')->get()->each(function (EpaperEdition $edition) use ($urls): void {
            $urls->push([
                'loc' => route('epaper.show', $edition->slug),
                'lastmod' => $edition->updated_at?->toAtomString() ?? $edition->published_at?->toAtomString(),
                'priority' => '0.7',
            ]);
        });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
