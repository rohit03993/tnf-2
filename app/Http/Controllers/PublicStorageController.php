<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicStorageController extends Controller
{
    public function __invoke(Request $request, ?string $path = null): BinaryFileResponse
    {
        $path = ltrim(str_replace('\\', '/', (string) $path), '/');

        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $fullPath = storage_path('app/public/'.$path);

        if (! is_file($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath);
    }
}
