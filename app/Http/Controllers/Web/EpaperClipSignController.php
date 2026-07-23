<?php

namespace App\Http\Controllers\Web;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\EpaperEdition;
use App\Services\EpaperClipCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EpaperClipSignController extends Controller
{
    public function __invoke(Request $request, EpaperEdition $edition): JsonResponse
    {
        abort_unless(
            $edition->status === ContentStatus::Published
            && $edition->published_at
            && $edition->published_at <= now(),
            404,
        );

        $validated = $request->validate([
            'page' => ['required', 'integer', 'min:1'],
            'x' => ['required', 'numeric', 'min:0', 'max:1'],
            'y' => ['required', 'numeric', 'min:0', 'max:1'],
            'w' => ['required', 'numeric', 'min:0.01', 'max:1'],
            'h' => ['required', 'numeric', 'min:0.01', 'max:1'],
        ]);

        $clip = [
            'page' => (int) $validated['page'],
            'x' => (float) $validated['x'],
            'y' => (float) $validated['y'],
            'w' => (float) $validated['w'],
            'h' => (float) $validated['h'],
        ];

        $token = EpaperClipCodeService::encode($edition->id, $clip);

        return response()->json([
            'url' => route('epaper.clip.short', ['token' => $token]),
            'token' => $token,
            'signed' => true,
        ]);
    }
}
