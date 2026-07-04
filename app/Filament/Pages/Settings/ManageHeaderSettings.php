<?php

namespace App\Filament\Pages\Settings;

use App\Enums\UserRole;
use App\Filament\Pages\Settings\Concerns\ManagesSettings;
use App\Services\BrandLogoService;
use App\Services\PwaIconService;
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
            'site_favicon' => '',
            'pwa_icon' => '',
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
                Section::make('Site icon (PWA & favicon)')
                    ->description('Square TNF logo used for the browser tab favicon, bookmarks, and when users install the app on their phone home screen. Use your square mark — not the wide header logo. Recommended 512×512 PNG.')
                    ->schema([
                        FileUpload::make('pwa_icon')
                            ->label('Square site icon')
                            ->disk('public')
                            ->directory('settings/pwa/uploads')
                            ->visibility('public')
                            ->image()
                            ->imagePreviewHeight('120')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->maxSize(2048)
                            ->helperText('Square PNG recommended (512×512). Powers favicon, PWA install icon, and iOS home screen.'),
                    ]),
                Section::make('Browser favicon override')
                    ->description('Optional. Leave empty to use the square site icon above for the browser tab. Upload only if you need a different small favicon.')
                    ->schema([
                        FileUpload::make('site_favicon')
                            ->label('Custom favicon (optional)')
                            ->disk('public')
                            ->directory('settings/favicon')
                            ->visibility('public')
                            ->imagePreviewHeight('64')
                            ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon'])
                            ->maxSize(512)
                            ->helperText('Used only when no square site icon is uploaded.'),
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

        if (array_key_exists('site_favicon', $data)) {
            $incoming = $data['site_favicon'];
            $path = is_array($incoming) ? ($incoming[0] ?? null) : $incoming;
            $data['site_favicon'] = filled($path) ? (string) $path : '';
        }

        if (array_key_exists('pwa_icon', $data)) {
            $incoming = $data['pwa_icon'];
            $path = is_array($incoming) ? ($incoming[0] ?? null) : $incoming;

            if (filled($path)) {
                try {
                    $data['pwa_icon'] = PwaIconService::process('public', (string) $path);
                } catch (\Throwable $exception) {
                    Notification::make()
                        ->title('PWA icon upload failed')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }
            } else {
                \Illuminate\Support\Facades\Storage::disk('public')->delete(PwaIconService::CANONICAL_PATH);
                $data['pwa_icon'] = '';
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
