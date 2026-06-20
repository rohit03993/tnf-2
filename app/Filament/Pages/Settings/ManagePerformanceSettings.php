<?php

namespace App\Filament\Pages\Settings;

use App\Enums\UserRole;
use App\Filament\Pages\Settings\Concerns\ManagesSettings;
use App\Services\PageCacheService;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManagePerformanceSettings extends SettingsPage
{
    use ManagesSettings;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $navigationLabel = 'Performance';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Performance';

    protected static ?string $slug = 'settings/performance';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function settingKeys(): array
    {
        return [
            'page_cache_enabled' => config('tnf.page_cache_enabled', env('APP_ENV') !== 'local'),
            'page_cache_ttl' => config('tnf.page_cache_ttl', 300),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Public page cache')
                ->description('Caches full HTML for anonymous visitors. Use “Refresh public page cache” in System after deploys.')
                ->schema([
                    Toggle::make('page_cache_enabled')
                        ->label('Enable full-page cache'),
                    TextInput::make('page_cache_ttl')
                        ->label('Cache TTL (seconds)')
                        ->numeric()
                        ->minValue(60)
                        ->maxValue(3600),
                    Placeholder::make('cache_version')
                        ->label('Current cache version')
                        ->content(fn (): string => (string) PageCacheService::version()),
                    Placeholder::make('cache_driver')
                        ->label('Cache driver')
                        ->content(fn (): string => (string) config('cache.default')),
                    Placeholder::make('production_hint')
                        ->label('Production checklist')
                        ->content('Set CACHE_STORE=redis, QUEUE_CONNECTION=redis, and put Cloudflare in front of static assets and /storage.')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}
