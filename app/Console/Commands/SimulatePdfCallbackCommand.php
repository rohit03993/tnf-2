<?php

namespace App\Console\Commands;

use App\Models\EpaperEdition;
use App\Services\PdfCallbackService;
use Illuminate\Console\Command;

class SimulatePdfCallbackCommand extends Command
{
    protected $signature = 'tnf:simulate-pdf-callback {edition : Edition ID or slug}';

    protected $description = 'Simulate a successful PDF service callback (local dev without FastAPI)';

    public function handle(PdfCallbackService $callbackService): int
    {
        $edition = EpaperEdition::query()
            ->where('id', $this->argument('edition'))
            ->orWhere('slug', $this->argument('edition'))
            ->first();

        if (! $edition) {
            $this->error('Edition not found.');

            return self::FAILURE;
        }

        $edition->load('featuredMedia');

        $coverUrl = $edition->featuredMedia?->url();

        if (! $coverUrl) {
            $this->error('Edition needs a cover image or existing pages to simulate.');

            return self::FAILURE;
        }

        $pages = collect(range(1, 4))->map(fn (int $page) => [
            'page' => $page,
            'url' => $coverUrl,
            'width' => 600,
            'height' => 800,
        ])->all();

        $callbackService->handle([
            'external_id' => 'edition-'.$edition->id,
            'job_id' => 'sim-'.now()->timestamp,
            'status' => 'ready',
            'pages' => $pages,
        ]);

        $edition->refresh();

        $this->info("Simulated callback for [{$edition->title}] — pdf_status: {$edition->pdf_status->value}");

        return self::SUCCESS;
    }
}
