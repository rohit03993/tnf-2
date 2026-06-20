<?php

namespace App\Jobs;

use App\Enums\PdfStatus;
use App\Models\EpaperEdition;
use App\Services\PdfClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class EnqueuePdfRenderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public EpaperEdition $edition) {}

    public function handle(PdfClient $pdfClient): void
    {
        $edition = $this->edition->fresh();

        if (! $edition || ! $edition->pdf_path) {
            return;
        }

        $edition->update([
            'pdf_status' => PdfStatus::Processing,
            'pdf_error' => null,
        ]);

        try {
            $result = $pdfClient->enqueue($edition);

            $edition->update([
                'pdf_status' => PdfStatus::Queued,
                'pdf_job_id' => $result['job_id'],
                'pdf_error' => null,
            ]);
        } catch (\Throwable $exception) {
            $edition->update([
                'pdf_status' => PdfStatus::Failed,
                'pdf_error' => $exception->getMessage(),
            ]);
        }

        Cache::forget('homepage.data');
    }
}
