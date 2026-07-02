<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AssetLinksController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $path = public_path('.well-known/assetlinks.json');

        if (! is_file($path)) {
            abort(404);
        }

        $payload = json_decode((string) file_get_contents($path), true);

        if (! is_array($payload)) {
            abort(404);
        }

        return response()->json($payload, 200, [], JSON_UNESCAPED_SLASHES);
    }
}
