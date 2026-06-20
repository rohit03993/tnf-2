<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\SeoService;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __invoke(string $slug, SeoService $seo): View
    {
        $page = Page::query()->where('slug', $slug)->firstOrFail();

        return view('pages.static', [
            'page' => $page,
            'title' => $page->title,
            'seo' => $seo->forPage($page),
        ]);
    }
}
