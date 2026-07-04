<?php

namespace App\Filament\Pages\Settings;

use App\Enums\UserRole;
use App\Services\OneSignalService;
use App\Services\PageCacheService;
use App\Services\PdfClient;
use App\Support\TnfSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ManageSystemTools extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'System';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'System';

    protected static ?string $slug = 'settings/system';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('linkStorage')
                ->label('Fix storage link')
                ->icon(Heroicon::OutlinedLink)
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (): void {
                    Artisan::call('tnf:fix-storage');

                    Notification::make()
                        ->title('Storage fixed')
                        ->body(trim(Artisan::output()) ?: 'Reload the ePaper edit page and save the cover again.')
                        ->success()
                        ->send();
                }),
            Action::make('clearCache')
                ->label('Clear app cache')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (): void {
                    Artisan::call('cache:clear');
                    \App\Services\ContentCacheService::bust();

                    Notification::make()->title('Application cache cleared')->success()->send();
                }),
            Action::make('bumpPageCache')
                ->label('Refresh public page cache')
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->action(function (): void {
                    \App\Services\ContentCacheService::bust();

                    Notification::make()->title('Public content cache refreshed')->success()->send();
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Health checks')
                ->description('Quick status for local troubleshooting.')
                ->schema([
                    Placeholder::make('database')->label('Database')->content(fn (): string => $this->databaseStatus()),
                    Placeholder::make('storage')->label('Public storage link')->content(fn (): string => $this->storageStatus()),
                    Placeholder::make('queue')->label('Queue driver')->content(fn (): string => (string) config('queue.default')),
                    Placeholder::make('pdf')->label('PDF service')->content(fn (): string => app(PdfClient::class)->isConfigured() ? 'Configured' : 'Not configured (PDF.js fallback)'),
                    Placeholder::make('push')->label('OneSignal push')->content(fn (): string => $this->pushStatus()),
                    Placeholder::make('app_url')->label('App URL')->content(fn (): string => (string) config('app.url')),
                    Placeholder::make('frontend_url')->label('Public URL')->content(fn (): string => (string) TnfSetting::get('frontend_url', config('app.url'))),
                    Placeholder::make('mobile')->label('Capacitor app')->content('Use ?tnf_app=1 in browser or TNFTodayCapacitor user-agent. See mobile-app/ for APK config.'),
                ])
                ->columns(2),
        ]);
    }

    protected function databaseStatus(): string
    {
        try {
            DB::connection()->getPdo();

            return 'Connected';
        } catch (\Throwable) {
            return 'Connection failed';
        }
    }

    protected function storageStatus(): string
    {
        if ($this->storageLinkWorks()) {
            return 'Linked';
        }

        $sample = glob(storage_path('app/public/epaper/covers/*')) ?: [];

        if ($sample !== []) {
            return 'Symlink missing, but /storage route fallback is active';
        }

        return 'Run “Fix storage link” or: php artisan tnf:fix-storage';
    }

    protected function storageLinkWorks(): bool
    {
        $link = public_path('storage');

        return file_exists($link) && is_link($link);
    }

    protected function pushStatus(): string
    {
        $service = app(OneSignalService::class);

        if ($service->isEnabled()) {
            return 'Enabled';
        }

        if ($service->isConfigured()) {
            return 'Configured (disabled in Push settings)';
        }

        return 'Not configured';
    }
}
