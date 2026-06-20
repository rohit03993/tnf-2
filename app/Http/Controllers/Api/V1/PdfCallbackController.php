<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PdfCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PdfCallbackController extends Controller
{
    public function __invoke(Request $request, PdfCallbackService $callbackService): JsonResponse
    {
        $edition = $callbackService->handle($request->all());

        return response()->json([
            'ok' => true,
            'edition_id' => $edition->id,
            'pdf_status' => $edition->pdf_status->value,
            'page_count' => count($edition->pages_json['pages'] ?? []),
        ]);
    }
}
