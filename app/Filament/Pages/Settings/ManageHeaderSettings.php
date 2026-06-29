<?php

namespace App\Filament\Pages\Settings;

use App\Enums\UserRole;
use App\Filament\Pages\Settings\Concerns\ManagesSettings;
use App\Services\BrandLogoService;
use App\Support\TnfImageUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use App\Models\Setting;

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
            'site_logo' => '',
            'banner_image' => '',
            'banner_link_url' => '',
            'whatsapp_url' => '',
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
                Section::make('Brand logo')
                    ->description('Upload your full-width Hindi logo (टीएनएफ टुडे). It displays large in the site header — no crop step.')
                    ->schema([
                        TnfImageUpload::logoField(
                            FileUpload::make('site_logo')
                                ->label('Site logo')
                                ->disk('public')
                                ->directory('settings/brand/uploads')
                                ->visibility('public')
                                ->imagePreviewHeight('120')
                        ),
                    ]),
                Section::make('Header promo')->schema([
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

    public function save(): void
    {
        $data = $this->form->getState();

        if (array_key_exists('site_logo', $data)) {
            $incoming = $data['site_logo'];
            $path = is_array($incoming) ? ($incoming[0] ?? null) : $incoming;

            if (filled($path)) {
                try {
                    $data['site_logo'] = BrandLogoService::process('public', (string) $path);
                } catch (\Throwable $exception) {
                    Notification::make()
                        ->title('Logo upload failed')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }
            } else {
                \Illuminate\Support\Facades\Storage::disk('public')->delete(BrandLogoService::CANONICAL_PATH);
                $data['site_logo'] = '';
            }
        }

        foreach ($data as $key => $value) {
            if (in_array($key, $this->secretKeys(), true) && blank($value)) {
                continue;
            }

            Setting::set($key, $value);
        }

        Notification::make()->title('Settings saved')->success()->send();
    }
}
