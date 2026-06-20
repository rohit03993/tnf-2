<?php

namespace App\Console\Commands;

use App\Models\EpaperEdition;
use App\Services\EpaperCoverService;
use Illuminate\Console\Command;

class GenerateEpaperCoverCommand extends Command
{
    protected $signature = 'tnf:epaper-cover
                            {edition? : Edition ID or slug}
                            {--all : Generate covers for all PDF editions missing a featured image}';

    protected $description = 'Generate ePaper archive cover JPEG from PDF page 1 (local fallback when PDF service is off)';

    public function handle(EpaperCoverService $covers): int
    {
        if ($this->option('all')) {
            $editions = EpaperEdition::query()
                ->whereNotNull('pdf_path')
                ->whereNull('featured_media_id')
                ->orderBy('id')
                ->get();

            if ($editions->isEmpty()) {
                $this->info('No editions need a cover.');

                return self::SUCCESS;
            }

            $generated = 0;

            foreach ($editions as $edition) {
                $reason = $covers->diagnose($edition);

                if ($reason === 'already_has_cover') {
                    $this->line("Already has cover: {$edition->title}");

                    continue;
                }

                if ($reason === 'pdf_file_missing') {
                    $this->warn("PDF file missing on disk ({$edition->pdf_path}): {$edition->title}");

                    continue;
                }

                if ($reason === 'no_renderer') {
                    $this->warn("No PDF renderer installed: {$edition->title}");

                    continue;
                }

                if ($covers->ensureCover($edition)) {
                    $generated++;
                    $this->line("Cover created: {$edition->title}");
                } else {
                    $this->warn("Cover generation failed: {$edition->title}");
                }
            }

            $this->info("Done. {$generated} cover(s) created.");

            return self::SUCCESS;
        }

        $editionArg = $this->argument('edition');

        if (! $editionArg) {
            $this->error('Provide an edition ID/slug or use --all.');

            return self::FAILURE;
        }

        $edition = EpaperEdition::query()
            ->where('id', $editionArg)
            ->orWhere('slug', $editionArg)
            ->first();

        if (! $edition) {
            $this->error('Edition not found.');

            return self::FAILURE;
        }

        $reason = $covers->diagnose($edition);

        if ($reason === 'already_has_cover') {
            $this->info("Edition already has a cover image.");

            return self::SUCCESS;
        }

        if ($reason === 'pdf_file_missing') {
            $this->error("PDF file not found on disk: {$edition->pdf_path}");
            $this->line('Re-upload the PDF in Admin → ePaper Editions, then run this command again.');

            return self::FAILURE;
        }

        if ($reason === 'no_renderer') {
            $this->error('No PDF renderer found on this PC.');
            $this->line('Install Ghostscript (recommended on Windows) and add it to PATH, or enable the PDF microservice.');

            return self::FAILURE;
        }

        if ($covers->ensureCover($edition)) {
            $this->info("Cover created for [{$edition->title}].");

            return self::SUCCESS;
        }

        $this->warn('Cover generation failed unexpectedly.');

        return self::FAILURE;
    }
}
