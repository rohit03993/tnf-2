<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\EpaperEdition;
use App\Services\PremiumAccess;
use App\Support\Api\WpContentSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $editions = EpaperEdition::query()
            ->published()
            ->with('featuredMedia')
            ->latest('published_at')
            ->paginate((int) $request->query('per_page', 15));

        return response()->json([
            'data' => $editions->getCollection()->map(fn (EpaperEdition $edition) => WpContentSerializer::epaper($edition))->values(),
            'meta' => [
                'current_page' => $editions->currentPage(),
                'last_page' => $editions->lastPage(),
                'per_page' => $editions->perPage(),
                'total' => $editions->total(),
            ],
        ]);
    }

    public function show(EpaperEdition $pdf): JsonResponse
    {
        abort_unless($this->isPublished($pdf), 404);

        return response()->json([
            'data' => WpContentSerializer::epaper($pdf->load('featuredMedia')),
        ]);
    }

    public function access(Request $request, EpaperEdition $pdf): JsonResponse
    {
        abort_unless($this->isPublished($pdf), 404);

        $user = $request->user();

        if ($pdf->restricted && ! PremiumAccess::canViewRestrictedEpaper($user, $pdf)) {
            return response()->json([
                'error' => $user ? 'premium_required' : 'authentication_required',
                'message' => $user
                    ? 'Active subscription required.'
                    : 'Please log in to access this edition.',
            ], 403);
        }

        return response()->json([
            'data' => WpContentSerializer::epaper($pdf->load('featuredMedia'), includeAccess: true),
        ]);
    }

    protected function isPublished(EpaperEdition $edition): bool
    {
        return $edition->status === ContentStatus::Published
            && $edition->published_at
            && $edition->published_at <= now();
    }
}
