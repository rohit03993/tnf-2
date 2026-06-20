<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EpaperEdition;
use Illuminate\View\View;

class EpaperArchiveController extends Controller
{
    public function __invoke(): View
    {
        $editions = EpaperEdition::query()
            ->published()
            ->with('featuredMedia')
            ->latest('published_at')
            ->get();

        return view('pages.epaper.index', compact('editions'));
    }
}
