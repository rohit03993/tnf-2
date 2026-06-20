<?php

namespace App\Services;

use App\Enums\PdfStatus;
use App\Jobs\EnqueuePdfRenderJob;
use App\Models\EpaperEdition;
use App\Support\TnfSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PdfProcessingService
{
    /**
     * Process a PDF upload automatically — no queue worker required by default.
     *
     * - No PDF microservice: mark ready immediately; public viewer reads PDF via PDF.js.
     * - PDF microservice configured: send enqueue request inline (fast HTTP call);
     *   page images arrive later via callback when rendering finishes.
     */
    public function process(EpaperEdition $edition): void
    {
        $edition = $edition->fresh();

        if (! $edition?->pdf_path) {
            return;
        }

        if ($this->shouldUseBackgroundQueue()) {
            EnqueuePdfRenderJob::dispatch($edition);

            return;
        }

        $this->processNow($edition);
    }

    protected function processNow(EpaperEdition $edition): void
    {
        $pdfClient = app(PdfClient::class);

        if ($pdfClient->isConfigured()) {
            app(EnqueuePdfRenderJob::class, ['edition' => $edition])->handle($pdfClient);

            return;
        }

        $edition->update([
            'pdf_status' => PdfStatus::Ready,
            'pdf_error' => null,
        ]);

        try {
            app(EpaperCoverService::class)->ensureCover($edition->fresh());
        } catch (\Throwable $exception) {
            Log::warning('ePaper cover generation skipped', [
                'edition_id' => $edition->id,
                'error' => $exception->getMessage(),
            ]);
        }

        Cache::forget('homepage.data');
    }

    protected function shouldUseBackgroundQueue(): bool
    {
        if (! TnfSetting::bool('pdf_use_queue', false)) {
            return false;
        }

        return config('queue.default') !== 'sync';
    }
}
