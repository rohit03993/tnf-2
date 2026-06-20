<?php

namespace App\Filament\Pages\Settings;

use App\Enums\UserRole;
use App\Filament\Pages\Settings\Concerns\ManagesSettings;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageHomepageSettings extends SettingsPage
{
    use ManagesSettings;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Homepage Controls';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Homepage Controls';

    protected static ?string $slug = 'settings/homepage';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function settingKeys(): array
    {
        return [
            'breaking_count' => 12,
            'top_stories_count' => 6,
            'featured_videos_count' => 4,
            'recent_news_count' => 9,
            'trending_count' => 8,
            'show_featured_videos' => true,
            'show_trending' => true,
            'show_crime' => true,
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
                Section::make('Section counts')->schema([
                    TextInput::make('breaking_count')->numeric()->required(),
                    TextInput::make('top_stories_count')
                        ->label('Hero stories count')
                        ->helperText('1 lead story plus the rest as headlines (e.g. 6 = 1 lead + 5 headlines).')
                        ->numeric()
                        ->required()
                        ->minValue(2)
                        ->maxValue(8),
                    TextInput::make('featured_videos_count')
                        ->label('Featured videos count')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(10)
                        ->helperText('Latest videos on homepage (1–10).'),
                    TextInput::make('recent_news_count')->numeric()->required(),
                    TextInput::make('trending_count')->numeric()->required(),
                ])->columns(2),
                Section::make('Visibility toggles')->schema([
                    Toggle::make('show_featured_videos'),
                    Toggle::make('show_trending'),
                    Toggle::make('show_crime'),
                ])->columns(2),
            ]);
    }
}
