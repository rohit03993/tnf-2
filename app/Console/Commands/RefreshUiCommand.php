<?php

namespace App\Console\Commands;

use App\Services\PageCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RefreshUiCommand extends Command
{
    protected $signature = 'tnf:refresh-ui {--build : Run npm run build before clearing caches}';

    protected $description = 'Clear Laravel caches so CSS/JS/Blade UI changes show in the browser';

    public function handle(): int
    {
        $hotFile = public_path('hot');

        if (is_file($hotFile)) {
            unlink($hotFile);
            $this->line('  removed stale public/hot (Vite was not running)');
        }

        if ($this->option('build')) {
            $this->info('Building frontend assets (npm run build)...');

            passthru('npm run build', $exitCode);

            if ($exitCode !== 0) {
                $this->error('npm run build failed.');

                return self::FAILURE;
            }
        }

        Artisan::call('view:clear');
        $this->line('  view cache cleared');

        Artisan::call('cache:clear');
        $this->line('  application cache cleared');

        PageCacheService::bump();
        $this->line('  public page cache version bumped');

        $manifest = public_path('build/manifest.json');

        if (is_file($manifest)) {
            $data = json_decode((string) file_get_contents($manifest), true);
            $css = $data['resources/css/app.css']['file'] ?? 'missing';
            $js = $data['resources/js/app.js']['file'] ?? 'missing';
            $this->newLine();
            $this->info('Active UI assets:');
            $this->line("  CSS: build/{$css}");
            $this->line("  JS:  build/{$js}");
        } else {
            $this->warn('No build/manifest.json — run: npm run build');
        }

        $this->newLine();
        $this->comment('Hard-refresh the browser (Ctrl+Shift+R).');
        $this->comment('While developing UI, keep `npm run dev` running in a second terminal.');

        return self::SUCCESS;
    }
}
