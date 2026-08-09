<?php

namespace App\Filament\Pages\Settings;

use App\Enums\UserRole;
use App\Filament\Pages\Settings\Concerns\ManagesSettings;
use App\Models\Setting;
use App\Services\WhatsAppCloudService;
use App\Support\TnfSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

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
            'whatsapp_access_token' => config('tnf.whatsapp_access_token'),
            'whatsapp_phone_number_id' => config('tnf.whatsapp_phone_number_id'),
            'whatsapp_business_account_id' => config('tnf.whatsapp_business_account_id'),
            'whatsapp_app_id' => config('tnf.whatsapp_app_id'),
            'whatsapp_app_secret' => config('tnf.whatsapp_app_secret'),
            'whatsapp_webhook_verify_token' => config('tnf.whatsapp_webhook_verify_token'),
            'whatsapp_graph_version' => config('tnf.whatsapp_graph_version', 'v21.0'),
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
        return ['whatsapp_access_token', 'whatsapp_app_secret'];
    }

    public function mount(): void
    {
        $data = $this->loadSettings();
        $data['whatsapp_access_token'] = '';
        $data['whatsapp_app_secret'] = '';
        $data['templates_readonly'] = $this->templateSummaryText();

        $this->form->fill($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveSettings')
                ->label('Save settings')
                ->icon(Heroicon::OutlinedCheck)
                ->action('save'),
            Action::make('testConnection')
                ->label('Test connection')
                ->icon(Heroicon::OutlinedSignal)
                ->action(function (): void {
                    $missing = [];
                    if (! filled(TnfSetting::get('whatsapp_access_token'))) {
                        $missing[] = 'Access token';
                    }
                    if (! filled(TnfSetting::get('whatsapp_phone_number_id'))) {
                        $missing[] = 'Phone number ID';
                    }

                    if ($missing !== []) {
                        Notification::make()
                            ->title('Cannot test yet')
                            ->body('Save these first, then test: '.implode(', ', $missing).'. (Token field looks blank after save on purpose.)')
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
                                $status['quality_rating'] ? 'Quality: '.$status['quality_rating'] : null,
                            ])->filter()->implode(' · ')))
                            ->success()
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Not connected')
                        ->body($status['error'] ?: 'Meta rejected the token / Phone number ID.')
                        ->danger()
                        ->persistent()
                        ->send();
                }),
            Action::make('syncTemplates')
                ->label('Sync templates')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(function (): void {
                    $result = app(WhatsAppCloudService::class)->syncMessageTemplates();

                    if (! $result['ok']) {
                        Notification::make()
                            ->title('Template sync failed')
                            ->body($result['error'] ?: 'Need Access token + numeric WABA ID saved first.')
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    $lines = collect(app(WhatsAppCloudService::class)->cachedMessageTemplates())
                        ->take(15)
                        ->map(fn (array $t): string => ($t['status'] ?? '?').' · '.($t['name'] ?? '').' ('.($t['language'] ?? '').')')
                        ->implode("\n");

                    Notification::make()
                        ->title('Templates synced ('.$result['approved'].' approved / '.$result['count'].' total)')
                        ->body($lines !== '' ? $lines : 'No templates returned.')
                        ->success()
                        ->persistent()
                        ->send();

                    $this->data['templates_readonly'] = $this->templateSummaryText();
                }),
        ];
    }

    public function save(): void
    {
        // Read Livewire state directly — do not hard-fail the whole save on one bad field.
        $data = is_array($this->data) ? $this->data : [];
        $saved = [];
        $warnings = [];

        foreach ($this->settingKeys() as $key => $default) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if (in_array($key, $this->secretKeys(), true) && blank($value)) {
                continue;
            }

            if ($key === 'whatsapp_business_account_id') {
                $value = trim((string) $value);
                if ($value !== '' && ! preg_match('/^\d+$/', $value)) {
                    $warnings[] = 'WABA ID was not saved (must be digits only like 111704343541197, not an email).';
                    continue;
                }
            }

            if ($key === 'whatsapp_webhook_verify_token') {
                $value = trim((string) $value);
                if ($value !== '' && str_contains($value, '#')) {
                    $warnings[] = 'Verify token was not saved (remove # from it).';
                    continue;
                }
            }

            if ($key === 'whatsapp_phone_number_id' || $key === 'whatsapp_app_id') {
                $value = is_string($value) ? trim($value) : $value;
            }

            Setting::set($key, $value);
            $saved[] = $key;
        }

        // Prove to the admin what is actually in the DB now.
        $proof = collect([
            filled(TnfSetting::get('whatsapp_access_token')) ? 'token=YES' : 'token=NO',
            filled(TnfSetting::get('whatsapp_phone_number_id')) ? 'phoneId=YES' : 'phoneId=NO',
            filled(TnfSetting::get('whatsapp_business_account_id')) ? 'waba=YES' : 'waba=NO',
            filled(TnfSetting::get('whatsapp_app_id')) ? 'appId=YES' : 'appId=NO',
            filled(TnfSetting::get('whatsapp_webhook_verify_token')) ? 'verify=YES' : 'verify=NO',
            TnfSetting::bool('whatsapp_enabled', false) ? 'enabled=ON' : 'enabled=OFF',
        ])->implode(' · ');

        if ($saved === []) {
            Notification::make()
                ->title('Nothing was saved')
                ->body('No field values reached the server. Re-type the values (do not rely on browser autofill) and click Save again. '.$proof)
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $body = 'Stored '.count($saved).' fields. DB check: '.$proof;
        if ($warnings !== []) {
            $body .= ' Warnings: '.implode(' ', $warnings);
        }
        $body .= ' Access token / App secret boxes go blank after save on purpose.';

        Notification::make()
            ->title($warnings === [] ? 'Settings saved' : 'Settings saved with warnings')
            ->body($body)
            ->success()
            ->persistent()
            ->send();

        // Reload non-secret fields from DB so you can see they stuck.
        $fresh = $this->loadSettings();
        $fresh['whatsapp_access_token'] = '';
        $fresh['whatsapp_app_secret'] = '';
        $fresh['templates_readonly'] = $this->templateSummaryText();
        $this->form->fill($fresh);
    }

    public function form(Schema $schema): Schema
    {
        $webhookUrl = url('/webhooks/whatsapp');

        return $schema->components([
            Section::make('Connection status')
                ->description('1) Fill fields 2) Save settings 3) Confirm green toast shows token=YES phoneId=YES 4) Test connection. Token boxes blank after save means stored, not lost.')
                ->schema([
                    Placeholder::make('secrets_status')
                        ->label('Currently stored on server')
                        ->content(fn (): string => collect([
                            filled(TnfSetting::get('whatsapp_access_token')) ? 'Access token: saved' : 'Access token: missing',
                            filled(TnfSetting::get('whatsapp_phone_number_id')) ? 'Phone number ID: '.TnfSetting::get('whatsapp_phone_number_id') : 'Phone number ID: missing',
                            filled(TnfSetting::get('whatsapp_business_account_id')) ? 'WABA: '.TnfSetting::get('whatsapp_business_account_id') : 'WABA: missing',
                            filled(TnfSetting::get('whatsapp_app_id')) ? 'App ID: '.TnfSetting::get('whatsapp_app_id') : 'App ID: missing',
                            filled(TnfSetting::get('whatsapp_webhook_verify_token')) ? 'Verify token: saved' : 'Verify token: missing',
                            TnfSetting::bool('whatsapp_enabled', false) ? 'Enabled: ON' : 'Enabled: OFF',
                        ])->implode(' | ')),
                    Placeholder::make('webhook_url')
                        ->label('Webhook callback URL')
                        ->content($webhookUrl),
                ]),

            Section::make('Meta API credentials')
                ->description('WABA must be numbers only (from Meta URL business_id). Never put your Gmail here.')
                ->schema([
                    Toggle::make('whatsapp_enabled')
                        ->label('Enable WhatsApp integration')
                        ->helperText('Leave OFF until token + phone number ID are saved and Test connection works.'),
                    TextInput::make('whatsapp_access_token')
                        ->label('Access token')
                        ->password()
                        ->revealable()
                        ->autocomplete(false)
                        ->helperText('Paste permanent token, then Save. Field clears after save if stored.')
                        ->columnSpanFull(),
                    TextInput::make('whatsapp_phone_number_id')
                        ->label('Phone number ID')
                        ->autocomplete(false)
                        ->helperText('Numeric ID from Meta → WhatsApp → API Setup.'),
                    TextInput::make('whatsapp_business_account_id')
                        ->label('WhatsApp Business Account ID (WABA)')
                        ->autocomplete(false)
                        ->helperText('Digits only. Example from your Meta URL: 111704343541197'),
                    TextInput::make('whatsapp_app_id')
                        ->label('Meta App ID')
                        ->autocomplete(false)
                        ->helperText('Example from your Meta URL: 953996650689050'),
                    TextInput::make('whatsapp_app_secret')
                        ->label('Meta App Secret')
                        ->password()
                        ->revealable()
                        ->autocomplete(false)
                        ->helperText('App settings → Basic → App secret. Optional until webhook signatures are required.'),
                    TextInput::make('whatsapp_webhook_verify_token')
                        ->label('Webhook verify token')
                        ->autocomplete(false)
                        ->helperText('Same string in Meta webhook. No # character. Example: tnfWhatsAppVerify2026'),
                    TextInput::make('whatsapp_graph_version')
                        ->label('Graph API version')
                        ->placeholder('v21.0'),
                ])->columns(2),

            Section::make('Templates (after sync)')
                ->schema([
                    Textarea::make('templates_readonly')
                        ->label('Last synced templates')
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(8)
                        ->helperText('Click Sync templates in the header after token + WABA are saved. Then copy an APPROVED name into the fields below.'),
                ]),

            Section::make('Assign template names')
                ->schema([
                    TextInput::make('whatsapp_otp_template')->label('OTP template name'),
                    TextInput::make('whatsapp_otp_template_lang')->label('OTP language')->default('en'),
                    TextInput::make('whatsapp_news_template')->label('News alert template'),
                    TextInput::make('whatsapp_news_template_lang')->label('News language')->default('en'),
                    TextInput::make('whatsapp_epaper_template')->label('ePaper alert template'),
                    TextInput::make('whatsapp_epaper_template_lang')->label('ePaper language')->default('en'),
                ])->columns(2),

            Section::make('Auto-share on publish')
                ->schema([
                    Toggle::make('whatsapp_on_news')
                        ->label('Default: send WhatsApp on news publish'),
                    Toggle::make('whatsapp_on_epaper')
                        ->label('Default: send WhatsApp on ePaper publish'),
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
                : 'Not synced yet. Save token + WABA, then click Sync templates.';
        }

        $lines = ["Last sync: ".($syncedAt ?: 'unknown')];
        foreach ($templates as $template) {
            $lines[] = ($template['status'] ?? '?')
                .' | '.($template['name'] ?? '')
                .' | '.($template['language'] ?? '')
                .' | '.($template['category'] ?? '');
        }

        return implode("\n", $lines);
    }
}
