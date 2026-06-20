<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Tag;
use Illuminate\View\View;

class TagController extends Controller
{
    public function __invoke(Tag $tag): View
    {
        $articles = Article::query()
            ->published()
            ->with('featuredMedia')
            ->whereHas('tags', fn ($query) => $query->where('tags.id', $tag->id))
            ->latest('published_at')
            ->paginate(12);

        return view('pages.archive', [
            'title' => '#'.$tag->name,
            'heading' => '#'.$tag->name,
            'description' => 'Stories tagged '.$tag->name,
            'articles' => $articles,
        ]);
    }
}
