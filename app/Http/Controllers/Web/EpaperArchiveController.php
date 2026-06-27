<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EpaperEdition;
use App\Services\SeoService;
use Illuminate\View\View;

class EpaperArchiveController extends Controller
{
    public function __invoke(SeoService $seo): View
    {
        $editions = EpaperEdition::query()
            ->published()
            ->with('featuredMedia')
            ->latest('published_at')
            ->get();

        return view('pages.epaper.index', [
            'editions' => $editions,
            'seo' => $seo->forEpaperIndex(),
        ]);
    }
}
