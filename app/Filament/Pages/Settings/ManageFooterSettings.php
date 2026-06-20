<?php

namespace App\Filament\Pages\Settings;

use App\Enums\UserRole;
use App\Filament\Pages\Settings\Concerns\ManagesSettings;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageFooterSettings extends SettingsPage
{
    use ManagesSettings;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3BottomRight;

    protected static ?string $navigationLabel = 'Footer';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'settings/footer';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function settingKeys(): array
    {
        return [
            'disclaimer_text' => '',
            'disclaimer_email' => 'contact@tnftoday.com',
            'credits_line' => 'Designed & Developed with Love by Pal Digital',
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
                RichEditor::make('disclaimer_text'),
                TextInput::make('disclaimer_email')->email(),
                TextInput::make('credits_line'),
            ]);
    }
}
