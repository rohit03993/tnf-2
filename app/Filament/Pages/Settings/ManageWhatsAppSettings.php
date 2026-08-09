<?php

namespace App\Filament\Pages\Settings;

use App\Enums\UserRole;
use App\Filament\Pages\Settings\Concerns\ManagesSettings;
use App\Models\Setting;
use App\Services\WhatsAppCloudService;
use App\Services\WhatsAppLiveCampaignService;
use App\Services\WhatsAppTemplateCatalogService;
use App\Support\TnfSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;

class ManageWhatsAppSettings extends SettingsPage
{
    use ManagesSettings;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleOvalLeftEllipsis;

    protected static ?string $navigationLabel = 'WhatsApp API';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'WhatsApp Meta API';

    protected static ?string $slug = 'settings/whatsapp';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function settingKeys(): array
    {
        return [
            'whatsapp_enabled' => false,
            'whatsapp_on_news' => false,
            'whatsapp_on_epaper' => true,
            'whatsapp_access_token' => '',
            'whatsapp_phone_number_id' => '',
            'whatsapp_business_account_id' => '',
            'whatsapp_app_id' => '',
            'whatsapp_app_secret' => '',
            'whatsapp_webhook_verify_token' => '',
            'whatsapp_graph_version' => 'v21.0',
            'whatsapp_otp_live_campaign_id' => '',
            'whatsapp_news_live_campaign_id' => '',
            'whatsapp_epaper_live_campaign_id' => '',
            'whatsapp_otp_template' => 'tnf_login_otp',
            'whatsapp_otp_template_lang' => 'en',
            'whatsapp_news_template' => 'tnf_news_alert',
            'whatsapp_news_template_lang' => 'en',
            'whatsapp_epaper_template' => 'tnf_epaper_alert',
            'whatsapp_epaper_template_lang' => 'en',
        ];
    }

    protected function secretKeys(): array
    {
        // Empty secret fields keep the previously saved value (same as Push settings).
        return ['whatsapp_access_token', 'whatsapp_app_secret'];
    }

    public function mount(): void
    {
        $this->form->fill($this->loadSettings());
    }

    protected function getHeaderActions(): array
    {
        return [
            // Must submit the form so Livewire receives typed field values.
            Action::make('save')
                ->label('Save settings')
                ->icon(Heroicon::OutlinedCheck)
                ->submit('save')
                ->formId('form'),
            Action::make('testConnection')
                ->label('Test connection')
                ->icon(Heroicon::OutlinedSignal)
                ->action(function (): void {
                    $missing = [];
                    if (! filled(Setting::get('whatsapp_access_token'))) {
                        $missing[] = 'Access token';
                    }
                    if (! filled(Setting::get('whatsapp_phone_number_id'))) {
                        $missing[] = 'Phone number ID';
                    }

                    if ($missing !== []) {
                        Notification::make()
                            ->title('Save credentials first')
                            ->body('Still missing in database: '.implode(', ', $missing))
                            ->warning()
                            ->persistent()
                            ->send();

                        return;
                    }

                    $status = app(WhatsAppCloudService::class)->connectionStatus();

                    if ($status['connected']) {
                        Notification::make()
                            ->title('WhatsApp connected')
                            ->body(trim(collect([
                                $status['verified_name'],
                                $status['display_phone_number'],
                                $status['business_name'] ? 'WABA: '.$status['business_name'] : null,
                            ])->filter()->implode(' · ')))
                            ->success()
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Not connected')
                        ->body($status['error'] ?: 'Check token and Phone number ID.')
                        ->danger()
                        ->persistent()
                        ->send();
                }),
            Action::make('syncTemplates')
                ->label('Sync templates')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(function (): void {
                    $result = app(WhatsAppTemplateCatalogService::class)->syncFromMeta();

                    if (! $result['ok']) {
                        Notification::make()
                            ->title('Template sync failed')
                            ->body($result['error'] ?: 'Save Access token + WABA first.')
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Templates synced')
                        ->body($result['approved'].' approved / '.$result['count'].' total. Open WhatsApp → Templates to manage them.')
                        ->success()
                        ->persistent()
                        ->send();

                    $this->form->fill($this->loadSettings() + [
                        'templates_readonly' => $this->templateSummaryText(),
                    ]);
                }),
        ];
    }

    public function save(): void
    {
        // Prefer validated form state; fall back to Livewire $data if needed.
        try {
            $data = $this->form->getState();
        } catch (\Throwable $e) {
            $data = is_array($this->data) ? $this->data : [];
            Log::warning('WhatsApp settings getState failed, using $this->data', [
                'error' => $e->getMessage(),
                'keys' => array_keys($data),
            ]);
        }

        if (! is_array($data) || $data === []) {
            $data = is_array($this->data) ? $this->data : [];
        }

        $savedKeys = [];

        foreach ($this->settingKeys() as $key => $default) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if (is_string($value)) {
                $value = trim($value);
            }

            if (in_array($key, $this->secretKeys(), true) && blank($value)) {
                continue;
            }

            // Do not let Chrome email autofill overwrite a good WABA.
            if ($key === 'whatsapp_business_account_id' && filled($value) && ! preg_match('/^\d+$/', (string) $value)) {
                Notification::make()
                    ->title('WABA must be digits only')
                    ->body('Remove the email autofill and paste the numeric WhatsApp Business Account ID, then Save again.')
                    ->danger()
                    ->persistent()
                    ->send();

                continue;
            }

            // Never wipe an existing important value with blank.
            if (blank($value) && filled(Setting::get($key)) && in_array($key, [
                'whatsapp_phone_number_id',
                'whatsapp_business_account_id',
                'whatsapp_app_id',
                'whatsapp_webhook_verify_token',
                'whatsapp_access_token',
                'whatsapp_app_secret',
            ], true)) {
                continue;
            }

            Setting::set($key, $value);
            $savedKeys[] = $key;
        }

        // Bust any leftover setting cache keys.
        foreach ($this->settingKeys() as $key => $default) {
            \Illuminate\Support\Facades\Cache::forget("setting.{$key}");
        }

        $phone = (string) Setting::get('whatsapp_phone_number_id', '');
        $waba = (string) Setting::get('whatsapp_business_account_id', '');
        $tokenOk = filled(Setting::get('whatsapp_access_token'));

        Log::info('WhatsApp settings save', [
            'saved_keys' => $savedKeys,
            'phone' => $phone,
            'waba' => $waba,
            'token' => $tokenOk,
        ]);

        if ($savedKeys === [] && ! $tokenOk && $phone === '') {
            Notification::make()
                ->title('Save did not store anything')
                ->body('Scroll down and click the Save settings button under the form as well, or re-paste values and try again. Do not use browser autofill.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title('Settings saved')
            ->body('Phone ID: '.($phone !== '' ? $phone : '—').' · WABA: '.($waba !== '' ? $waba : '—').' · Token: '.($tokenOk ? 'saved' : 'missing'))
            ->success()
            ->persistent()
            ->send();

        // Reload from DB so the “Stored on server” panel updates — keep typed values from DB.
        $this->form->fill($this->loadSettings() + [
            'templates_readonly' => $this->templateSummaryText(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $anti = [
            'autocomplete' => 'off',
            'data-lpignore' => 'true',
            'data-1p-ignore' => 'true',
            'data-bwignore' => 'true',
            'data-form-type' => 'other',
        ];

        return $schema->components([
            Section::make('Stored on server')
                ->description('After a successful Save, these lines must show your numbers. If they say missing, Save did not reach the database.')
                ->schema([
                    Placeholder::make('stored')
                        ->hiddenLabel()
                        ->content(fn (): string => collect([
                            filled(Setting::get('whatsapp_access_token')) ? 'Access token: saved' : 'Access token: missing',
                            filled(Setting::get('whatsapp_phone_number_id'))
                                ? 'Phone number ID: '.Setting::get('whatsapp_phone_number_id')
                                : 'Phone number ID: missing',
                            filled(Setting::get('whatsapp_business_account_id'))
                                ? 'WABA: '.Setting::get('whatsapp_business_account_id')
                                : 'WABA: missing',
                            filled(Setting::get('whatsapp_app_id'))
                                ? 'App ID: '.Setting::get('whatsapp_app_id')
                                : 'App ID: missing',
                            filled(Setting::get('whatsapp_webhook_verify_token'))
                                ? 'Verify token: '.Setting::get('whatsapp_webhook_verify_token')
                                : 'Verify token: missing',
                            filled(Setting::get('whatsapp_app_secret')) ? 'App secret: saved' : 'App secret: missing',
                            filter_var(Setting::get('whatsapp_enabled', false), FILTER_VALIDATE_BOOLEAN) ? 'Enabled: ON' : 'Enabled: OFF',
                        ])->implode("\n")),
                    Placeholder::make('webhook_url')
                        ->label('Webhook URL')
                        ->content(url('/webhooks/whatsapp')),
                ]),

            Section::make('Meta API credentials')
                ->description('Paste values, then click Save settings (top or bottom). Phone number ID and WABA are different numbers from Meta.')
                ->schema([
                    Toggle::make('whatsapp_enabled')
                        ->label('Enable WhatsApp integration'),
                    Textarea::make('whatsapp_access_token')
                        ->label('Access token')
                        ->rows(3)
                        ->extraInputAttributes($anti)
                        ->columnSpanFull(),
                    TextInput::make('whatsapp_phone_number_id')
                        ->label('Phone number ID')
                        ->extraInputAttributes($anti)
                        ->helperText('From Meta → WhatsApp → API Setup (Phone number ID).'),
                    TextInput::make('whatsapp_business_account_id')
                        ->label('WhatsApp Business Account ID (WABA)')
                        ->extraInputAttributes($anti)
                        ->helperText('Digits only. Different from Phone number ID.'),
                    TextInput::make('whatsapp_app_id')
                        ->label('Meta App ID')
                        ->extraInputAttributes($anti),
                    Textarea::make('whatsapp_app_secret')
                        ->label('Meta App Secret')
                        ->rows(2)
                        ->extraInputAttributes($anti),
                    TextInput::make('whatsapp_webhook_verify_token')
                        ->label('Webhook verify token')
                        ->extraInputAttributes($anti)
                        ->helperText('No # character.'),
                    TextInput::make('whatsapp_graph_version')
                        ->label('Graph API version')
                        ->extraInputAttributes($anti),
                ])->columns(2),

            Section::make('Live campaigns (system sends)')
                ->description('School-CRM style: create Live campaigns under WhatsApp → Live campaigns, Go live, then pick them here for OTP / news / ePaper.')
                ->schema([
                    Select::make('whatsapp_otp_live_campaign_id')
                        ->label('OTP live campaign')
                        ->options(fn (): array => app(WhatsAppLiveCampaignService::class)->liveOptions())
                        ->searchable()
                        ->helperText('Used for phone login OTP. Must be Live and linked to an AUTHENTICATION template.'),
                    Select::make('whatsapp_news_live_campaign_id')
                        ->label('News live campaign')
                        ->options(fn (): array => app(WhatsAppLiveCampaignService::class)->liveOptions())
                        ->searchable()
                        ->helperText('Used when publishing news (auto-share / campaigns).'),
                    Select::make('whatsapp_epaper_live_campaign_id')
                        ->label('ePaper live campaign')
                        ->options(fn (): array => app(WhatsAppLiveCampaignService::class)->liveOptions())
                        ->searchable()
                        ->helperText('Used when publishing ePaper editions.'),
                ])->columns(1),

            Section::make('Template sync / fallback names')
                ->description('Sync shows Meta templates. Fallback names are only used if no Live campaign is selected above.')
                ->schema([
                    Textarea::make('templates_readonly')
                        ->label('Last synced templates')
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(6)
                        ->afterStateHydrated(function (Textarea $component): void {
                            $component->state($this->templateSummaryText());
                        }),
                    TextInput::make('whatsapp_otp_template')->label('OTP template name (fallback)'),
                    TextInput::make('whatsapp_otp_template_lang')->label('OTP language (fallback)'),
                    TextInput::make('whatsapp_news_template')->label('News template (fallback)'),
                    TextInput::make('whatsapp_news_template_lang')->label('News language (fallback)'),
                    TextInput::make('whatsapp_epaper_template')->label('ePaper template (fallback)'),
                    TextInput::make('whatsapp_epaper_template_lang')->label('ePaper language (fallback)'),
                ])->columns(2)->collapsed(),

            Section::make('Auto-share on publish')
                ->schema([
                    Toggle::make('whatsapp_on_news')->label('WhatsApp on news publish'),
                    Toggle::make('whatsapp_on_epaper')->label('WhatsApp on ePaper publish'),
                ]),
        ]);
    }

    protected function templateSummaryText(): string
    {
        $syncedAt = Setting::get('whatsapp_templates_synced_at');
        $templates = app(WhatsAppCloudService::class)->cachedMessageTemplates();

        if ($templates === []) {
            return $syncedAt
                ? "Last sync: {$syncedAt}\n(No templates returned.)"
                : 'Not synced yet. Save token + WABA, then Sync templates.';
        }

        $lines = ['Last sync: '.($syncedAt ?: 'unknown')];
        foreach ($templates as $template) {
            $lines[] = ($template['status'] ?? '?')
                .' | '.($template['name'] ?? '')
                .' | '.($template['language'] ?? '');
        }

        return implode("\n", $lines);
    }
}
