<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PlaceholderController extends Controller
{
    public function __invoke(string $title): View
    {
        return view('pages.placeholder', compact('title'));
    }
}
