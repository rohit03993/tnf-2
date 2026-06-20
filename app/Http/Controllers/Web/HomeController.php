<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\HomepageService;
use App\Services\SeoService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(HomepageService $homepage, SeoService $seo): View
    {
        return view('pages.home', [
            ...$homepage->data(),
            'seo' => $seo->forHome(),
        ]);
    }
}
