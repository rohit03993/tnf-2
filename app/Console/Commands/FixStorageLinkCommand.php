<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class FixStorageLinkCommand extends Command
{
    protected $signature = 'tnf:fix-storage';

    protected $description = 'Remove the empty public/storage folder and create the correct storage symlink';

    public function handle(): int
    {
        $link = public_path('storage');

        if (file_exists($link)) {
            if (is_link($link)) {
                @unlink($link);
            } else {
                $this->warn('Removing public/storage (empty folder or Windows junction blocking the symlink).');
                File::deleteDirectory($link);
            }
        }

        Artisan::call('storage:link', ['--force' => true]);
        $this->output->write(Artisan::output());

        if (is_link($link) || is_dir($link)) {
            $this->info('Storage link is ready.');

            return self::SUCCESS;
        }

        $this->warn('Symlink could not be created. Local /storage route fallback will still serve uploaded files.');

        return self::SUCCESS;
    }
}
