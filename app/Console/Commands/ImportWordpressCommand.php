<?php

namespace App\Console\Commands;

use App\Services\WordPress\NewsWxrImporter;
use Illuminate\Console\Command;

class ImportWordpressCommand extends Command
{
    protected $signature = 'tnf:import-wordpress
                            {path : Path to WordPress WXR export (.xml)}
                            {--dry-run : Count items without writing to the database}
                            {--author-id= : Assign all imported news to this user ID}
                            {--no-update : Skip articles that already exist (match by slug)}';

    protected $description = 'Import news articles from a WordPress Tools → Export XML file (no images/PDFs)';

    public function handle(NewsWxrImporter $importer): int
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        if (! str_ends_with(strtolower($path), '.xml')) {
            $this->warn('Expected a WordPress .xml export file (Tools → Export).');
        }

        $this->info('Importing news only (post + tnf_news). Images, PDFs, and videos are skipped.');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->comment('Dry run — no database changes.');
        }

        $stats = $importer->import(
            path: $path,
            dryRun: (bool) $this->option('dry-run'),
            updateExisting: ! $this->option('no-update'),
            authorId: $this->option('author-id') ? (int) $this->option('author-id') : null,
        );

        $this->table(
            ['Result', 'Count'],
            [
                ['Categories from export', $stats['categories']],
                ['Imported', $stats['imported']],
                ['Updated', $stats['updated']],
                ['Skipped', $stats['skipped']],
                ['Errors', count($stats['errors'])],
            ],
        );

        foreach ($stats['errors'] as $error) {
            $this->error($error);
        }

        if ($stats['errors'] !== []) {
            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->line('Run without --dry-run to write articles.');
        } else {
            $this->newLine();
            $this->info('Done. Generate a dev seeder with: php artisan tnf:export-news-seeder');
        }

        return self::SUCCESS;
    }
}
