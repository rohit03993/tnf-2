<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Support\LegalPageContent;
use Illuminate\Console\Command;

class SyncLegalPagesCommand extends Command
{
    protected $signature = 'tnf:sync-legal-pages';

    protected $description = 'Update Privacy Policy and Terms of Use content for the public site';

    public function handle(): int
    {
        foreach (LegalPageContent::pages() as $page) {
            Page::query()->updateOrCreate(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'content' => $page['content'],
                ],
            );

            $this->info("Synced: {$page['title']} ({$page['slug']})");
        }

        $this->newLine();
        $this->line('Privacy: '.url('/privacy-policy'));
        $this->line('Terms: '.url('/terms-of-use'));

        return self::SUCCESS;
    }
}
