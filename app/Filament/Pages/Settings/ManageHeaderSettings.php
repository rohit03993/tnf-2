<?php

namespace App\Filament\Pages\Settings;

use App\Enums\UserRole;
use App\Filament\Pages\Settings\Concerns\ManagesSettings;
use App\Support\TnfImageUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageHeaderSettings extends SettingsPage
{
    use ManagesSettings;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3BottomLeft;

    protected static ?string $navigationLabel = 'Header';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'settings/header';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function settingKeys(): array
    {
        return [
            'banner_image' => '',
            'banner_link_url' => '',
            'whatsapp_url' => '',
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
                Section::make()->schema([
                    TnfImageUpload::applyTo(
                        FileUpload::make('banner_image')
                            ->image()
                            ->disk('public')
                            ->directory('settings/header')
                    ),
                    TextInput::make('banner_link_url')->url(),
                    TextInput::make('whatsapp_url')->label('WhatsApp promo URL')->url(),
                ]),
            ]);
    }
}
