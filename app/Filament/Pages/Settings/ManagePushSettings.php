<?php

namespace App\Filament\Pages\Settings;

use App\Enums\UserRole;
use App\Filament\Pages\Settings\Concerns\ManagesSettings;
use App\Services\OneSignalService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManagePushSettings extends SettingsPage
{
    use ManagesSettings;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static ?string $navigationLabel = 'Push Notifications';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Push Notifications';

    protected static ?string $slug = 'settings/push';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function settingKeys(): array
    {
        return [
            'push_enabled' => false,
            'push_on_news' => true,
            'push_on_videos' => true,
            'push_on_epaper' => true,
            'onesignal_app_id' => config('tnf.onesignal_app_id'),
            'onesignal_rest_key' => config('tnf.onesignal_rest_key'),
            'frontend_url' => config('tnf.frontend_url', config('app.url')),
        ];
    }

    protected function secretKeys(): array
    {
        return ['onesignal_rest_key'];
    }

    public function mount(): void
    {
        $data = $this->loadSettings();
        $data['onesignal_rest_key'] = '';

        $this->form->fill($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testPush')
                ->label('Send test notification')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->requiresConfirmation()
                ->modalDescription('Sends a test push to the “All” segment in OneSignal.')
                ->visible(fn (): bool => app(OneSignalService::class)->isConfigured())
                ->action(function (): void {
                    $service = app(OneSignalService::class);

                    if (! $service->isConfigured()) {
                        Notification::make()
                            ->title('OneSignal keys missing')
                            ->warning()
                            ->send();

                        return;
                    }

                    $wasEnabled = \App\Support\TnfSetting::bool('push_enabled', false);
                    if (! $wasEnabled) {
                        \App\Models\Setting::set('push_enabled', true);
                    }

                    $sent = $service->sendTest();

                    if (! $wasEnabled) {
                        \App\Models\Setting::set('push_enabled', false);
                    }

                    if ($sent) {
                        Notification::make()
                            ->title('Test push sent')
                            ->body('Check subscribed devices in OneSignal.')
                            ->success()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Push failed')
                        ->body('Check storage/logs/laravel.log for OneSignal response details.')
                        ->danger()
                        ->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('OneSignal')
                ->description('Get keys from OneSignal → Settings → Keys & IDs. Enable push only when ready.')
                ->schema([
                    Toggle::make('push_enabled')
                        ->label('Enable push notifications')
                        ->helperText('When off, nothing is sent even if keys are saved.'),
                    Toggle::make('push_on_news')
                        ->label('Notify on news publish'),
                    Toggle::make('push_on_videos')
                        ->label('Notify on video publish'),
                    Toggle::make('push_on_epaper')
                        ->label('Notify on ePaper publish'),
                    TextInput::make('onesignal_app_id')
                        ->label('App ID'),
                    TextInput::make('onesignal_rest_key')
                        ->label('REST API key')
                        ->password()
                        ->revealable()
                        ->helperText('Leave blank to keep the current key.'),
                    TextInput::make('frontend_url')
                        ->label('Public site URL')
                        ->url()
                        ->helperText('Used for notification deep links (e.g. https://tnftoday.com).'),
                ]),
        ]);
    }
}
