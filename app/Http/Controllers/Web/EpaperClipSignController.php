<?php

namespace App\Http\Controllers\Web;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\EpaperEdition;
use App\Services\EpaperClipSignatureService;
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

        $url = route('epaper.show', $edition->slug);
        $query = array_merge([
            'tnf_clip' => '1',
            'tnf_pg' => $clip['page'],
            'tnf_cx' => number_format($clip['x'], 4, '.', ''),
            'tnf_cy' => number_format($clip['y'], 4, '.', ''),
            'tnf_cw' => number_format($clip['w'], 4, '.', ''),
            'tnf_ch' => number_format($clip['h'], 4, '.', ''),
        ], EpaperClipSignatureService::sign($edition, $clip));

        return response()->json([
            'url' => $url.'?'.http_build_query($query),
            'signed' => EpaperClipSignatureService::signingEnabled(),
        ]);
    }
}
