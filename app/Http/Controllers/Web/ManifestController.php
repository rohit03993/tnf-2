<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\PwaManifestService;
use Illuminate\Http\JsonResponse;

class ManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(
            PwaManifestService::manifest(),
            200,
            ['Content-Type' => 'application/manifest+json'],
            JSON_UNESCAPED_SLASHES,
        );
    }
}
