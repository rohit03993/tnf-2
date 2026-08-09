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

    /** @return array<string, string> */
    protected function antiAutofillAttributes(): array
    {
        return [
            'autocomplete' => 'off',
            'data-lpignore' => 'true',
            'data-1p-ignore' => 'true',
            'data-bwignore' => 'true',
            'data-form-type' => 'other',
            'spellcheck' => 'false',
        ];
    }

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
        $this->fillFromDatabase();

        // Re-apply DB values after Chrome autofill runs.
        $this->js(<<<'JS'
            setTimeout(() => $wire.call('refillFromDatabase'), 400);
            setTimeout(() => $wire.call('refillFromDatabase'), 1200);
        JS);
    }

    public function refillFromDatabase(): void
    {
        $this->fillFromDatabase();
    }

    protected function fillFromDatabase(): void
    {
        // Always reload exactly what is stored — including token/secret — so refresh keeps values.
        $data = $this->loadSettings();

        foreach ($this->settingKeys() as $key => $default) {
            $stored = Setting::get($key, $default);
            if (is_bool($default)) {
                $data[$key] = filter_var($stored, FILTER_VALIDATE_BOOLEAN);
            } else {
                $data[$key] = $stored ?? '';
            }
        }

        $data['templates_readonly'] = $this->templateSummaryText();
        $data['autofill_trap_user'] = '';
        $data['autofill_trap_pass'] = '';

        $this->form->fill($data);
        $this->data = array_merge(is_array($this->data) ? $this->data : [], $data);
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
                            ->body('Save these first: '.implode(', ', $missing).'. Ignore Gmail autofill in the boxes — look at “Stored on server” above the form.')
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
        $raw = $this->form->getRawState();
        $raw = is_array($raw) ? $raw : (array) $raw;
        $data = array_merge(is_array($this->data) ? $this->data : [], $raw);

        $saved = [];
        $warnings = [];

        foreach ($this->settingKeys() as $key => $default) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if (in_array($key, $this->secretKeys(), true) && blank($value)) {
                // Empty box = keep existing saved secret (do not wipe).
                continue;
            }

            if ($key === 'whatsapp_access_token') {
                $value = trim((string) $value);
                // Browser often autofills the site login password into this box.
                if (strlen($value) < 40 || str_contains($value, '@') || str_contains($value, ' ')) {
                    $warnings[] = 'Access token autofill ignored — kept your previously saved Meta token.';
                    continue;
                }
            }

            if ($key === 'whatsapp_business_account_id') {
                $value = trim((string) $value);
                if ($value !== '' && ! preg_match('/^\d+$/', $value)) {
                    $warnings[] = 'WABA autofill (email) ignored — kept your previously saved WABA ID.';
                    continue;
                }
            }

            if ($key === 'whatsapp_phone_number_id' || $key === 'whatsapp_app_id') {
                $value = is_string($value) ? trim($value) : $value;
                if (is_string($value) && $value !== '' && ! preg_match('/^\d+$/', $value)) {
                    $warnings[] = str_replace('whatsapp_', '', $key).' invalid — kept previous saved value.';
                    continue;
                }
            }

            if ($key === 'whatsapp_webhook_verify_token') {
                $value = trim((string) $value);
                if ($value !== '' && str_contains($value, '#')) {
                    $warnings[] = 'Verify token not updated (remove #). Kept previous value.';
                    continue;
                }
            }

            // Never write empty string over an already-saved important ID.
            if (in_array($key, [
                'whatsapp_phone_number_id',
                'whatsapp_business_account_id',
                'whatsapp_app_id',
                'whatsapp_webhook_verify_token',
                'whatsapp_access_token',
                'whatsapp_app_secret',
            ], true) && blank($value) && filled(Setting::get($key))) {
                continue;
            }

            Setting::set($key, $value);
            $saved[] = $key;
        }

        $phone = (string) TnfSetting::get('whatsapp_phone_number_id', '');
        $waba = (string) TnfSetting::get('whatsapp_business_account_id', '');
        $proof = collect([
            filled(TnfSetting::get('whatsapp_access_token')) ? 'token=YES' : 'token=NO',
            $phone !== '' ? 'phoneId='.$phone : 'phoneId=NO',
            $waba !== '' ? 'waba='.$waba : 'waba=NO',
            filled(TnfSetting::get('whatsapp_app_id')) ? 'appId=YES' : 'appId=NO',
            filled(TnfSetting::get('whatsapp_webhook_verify_token')) ? 'verify=YES' : 'verify=NO',
        ])->implode(' · ');

        if ($saved === []) {
            Notification::make()
                ->title('Nothing useful was saved')
                ->body('Chrome autofill may be filling Gmail/password into these boxes. Click each field, delete autofill, paste Meta values, then Save. '.$proof)
                ->danger()
                ->persistent()
                ->send();

            $this->fillFromDatabase();

            return;
        }

        Notification::make()
            ->title($warnings === [] ? 'Settings saved — they will stay after refresh' : 'Saved with warnings — previous good values were kept')
            ->body(trim('DB now has: '.$proof.($warnings !== [] ? ' | '.implode(' ', $warnings) : '')))
            ->success()
            ->persistent()
            ->send();

        $this->fillFromDatabase();
    }

    public function form(Schema $schema): Schema
    {
        $webhookUrl = url('/webhooks/whatsapp');
        $anti = $this->antiAutofillAttributes();

        return $schema->components([
            Section::make('Stored on server (always kept)')
                ->description('These values are loaded from the database every time you open this page. After a good Save, they stay forever until you change them.')
                ->schema([
                    Placeholder::make('secrets_status')
                        ->hiddenLabel()
                        ->content(fn (): string => collect([
                            filled(TnfSetting::get('whatsapp_access_token')) ? 'Access token: SAVED (shown in form below)' : 'Access token: missing',
                            filled(TnfSetting::get('whatsapp_phone_number_id'))
                                ? 'Phone number ID: '.TnfSetting::get('whatsapp_phone_number_id')
                                : 'Phone number ID: missing',
                            filled(TnfSetting::get('whatsapp_business_account_id'))
                                ? 'WABA: '.TnfSetting::get('whatsapp_business_account_id')
                                : 'WABA: missing',
                            filled(TnfSetting::get('whatsapp_app_id'))
                                ? 'App ID: '.TnfSetting::get('whatsapp_app_id')
                                : 'App ID: missing',
                            filled(TnfSetting::get('whatsapp_webhook_verify_token'))
                                ? 'Verify token: '.TnfSetting::get('whatsapp_webhook_verify_token')
                                : 'Verify token: missing',
                            filled(TnfSetting::get('whatsapp_app_secret')) ? 'App secret: SAVED (shown in form below)' : 'App secret: missing',
                            TnfSetting::bool('whatsapp_enabled', false) ? 'Enabled: ON' : 'Enabled: OFF',
                        ])->implode("\n")),
                    Placeholder::make('webhook_url')
                        ->label('Webhook callback URL')
                        ->content($webhookUrl),
                ]),

            Section::make('Meta API credentials')
                ->description('Save once — values reload from the server on every visit. If Chrome puts Gmail in a box, wait 1 second (we restore saved values) or paste again and Save.')
                ->schema([
                    // Decoy fields so Chrome autofills these instead of real Meta fields.
                    TextInput::make('autofill_trap_user')
                        ->label('Ignore')
                        ->default('')
                        ->dehydrated(false)
                        ->extraInputAttributes([
                            'autocomplete' => 'username',
                            'tabindex' => '-1',
                            'style' => 'position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;',
                        ]),
                    TextInput::make('autofill_trap_pass')
                        ->label('Ignore')
                        ->password()
                        ->default('')
                        ->dehydrated(false)
                        ->extraInputAttributes([
                            'autocomplete' => 'current-password',
                            'tabindex' => '-1',
                            'style' => 'position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;',
                        ]),
                    Toggle::make('whatsapp_enabled')
                        ->label('Enable WhatsApp integration')
                        ->helperText('Leave OFF until Test connection works.'),
                    Textarea::make('whatsapp_access_token')
                        ->label('Access token')
                        ->rows(3)
                        ->extraInputAttributes($anti + ['autocomplete' => 'new-password'])
                        ->helperText('Saved value stays here after refresh. Leave unchanged to keep it.')
                        ->columnSpanFull(),
                    TextInput::make('whatsapp_phone_number_id')
                        ->label('Phone number ID')
                        ->extraInputAttributes($anti)
                        ->helperText('Stays saved forever until you change it.'),
                    TextInput::make('whatsapp_business_account_id')
                        ->label('WhatsApp Business Account ID (WABA)')
                        ->extraInputAttributes($anti)
                        ->helperText('Digits only — never an email. Stays saved forever.'),
                    TextInput::make('whatsapp_app_id')
                        ->label('Meta App ID')
                        ->extraInputAttributes($anti)
                        ->helperText('Stays saved forever until you change it.'),
                    Textarea::make('whatsapp_app_secret')
                        ->label('Meta App Secret')
                        ->rows(2)
                        ->extraInputAttributes($anti + ['autocomplete' => 'new-password'])
                        ->helperText('Saved value stays here after refresh.'),
                    TextInput::make('whatsapp_webhook_verify_token')
                        ->label('Webhook verify token')
                        ->extraInputAttributes($anti)
                        ->helperText('Same string in Meta. No #. Stays saved forever.'),
                    TextInput::make('whatsapp_graph_version')
                        ->label('Graph API version')
                        ->extraInputAttributes($anti)
                        ->placeholder('v21.0'),
                ])->columns(2),

            Section::make('Templates (after sync)')
                ->schema([
                    Textarea::make('templates_readonly')
                        ->label('Last synced templates')
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(8)
                        ->helperText('Save token + WABA, then Sync templates. Copy an APPROVED name into the fields below.'),
                ]),

            Section::make('Assign template names')
                ->schema([
                    TextInput::make('whatsapp_otp_template')->label('OTP template name')->extraInputAttributes($anti),
                    TextInput::make('whatsapp_otp_template_lang')->label('OTP language')->default('en')->extraInputAttributes($anti),
                    TextInput::make('whatsapp_news_template')->label('News alert template')->extraInputAttributes($anti),
                    TextInput::make('whatsapp_news_template_lang')->label('News language')->default('en')->extraInputAttributes($anti),
                    TextInput::make('whatsapp_epaper_template')->label('ePaper alert template')->extraInputAttributes($anti),
                    TextInput::make('whatsapp_epaper_template_lang')->label('ePaper language')->default('en')->extraInputAttributes($anti),
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

        $lines = ['Last sync: '.($syncedAt ?: 'unknown')];
        foreach ($templates as $template) {
            $lines[] = ($template['status'] ?? '?')
                .' | '.($template['name'] ?? '')
                .' | '.($template['language'] ?? '')
                .' | '.($template['category'] ?? '');
        }

        return implode("\n", $lines);
    }
}
