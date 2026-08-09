<?php

namespace App\Filament\Pages\Settings;

use App\Enums\UserRole;
use App\Filament\Pages\Settings\Concerns\ManagesSettings;
use App\Models\Setting;
use App\Services\WhatsAppCloudService;
use App\Support\TnfSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

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
        $data['pick_otp_template'] = null;
        $data['pick_news_template'] = null;
        $data['pick_epaper_template'] = null;

        $this->form->fill($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testConnection')
                ->label('Test connection')
                ->icon(Heroicon::OutlinedSignal)
                ->action(function (): void {
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
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Not connected')
                        ->body($status['error'] ?: 'Check token and Phone Number ID.')
                        ->danger()
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
                            ->body($result['error'] ?: 'Could not fetch templates from Meta.')
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Templates synced')
                        ->body($result['count'].' total · '.$result['approved'].' approved. Use the pickers below to assign them.')
                        ->success()
                        ->send();

                    $this->redirect(static::getUrl());
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $whatsApp = app(WhatsAppCloudService::class);
        $connected = filter_var(Setting::get('whatsapp_connected', false), FILTER_VALIDATE_BOOLEAN);
        $displayPhone = Setting::get('whatsapp_display_phone');
        $verifiedName = Setting::get('whatsapp_verified_name');
        $quality = Setting::get('whatsapp_quality_rating');
        $lastChecked = Setting::get('whatsapp_last_checked_at');
        $webhookUrl = url('/webhooks/whatsapp');
        $templates = $whatsApp->cachedMessageTemplates();
        $syncedAt = Setting::get('whatsapp_templates_synced_at');
        $pickOptions = $whatsApp->approvedTemplatePickOptions();

        return $schema->components([
            Section::make('Connection status')
                ->description('Save credentials first, then click “Test connection” in the header.')
                ->schema([
                    Placeholder::make('connection_badge')
                        ->label('Status')
                        ->content(fn (): string => $connected
                            ? 'Connected to Meta WhatsApp Cloud API'
                            : (TnfSetting::bool('whatsapp_enabled', false) ? 'Enabled but not verified yet' : 'Not connected')),
                    Placeholder::make('connection_details')
                        ->label('Number / business')
                        ->content(fn (): string => collect([
                            $verifiedName,
                            $displayPhone,
                            $quality ? 'Quality: '.$quality : null,
                            $lastChecked ? 'Checked: '.$lastChecked : null,
                        ])->filter()->implode(' · ') ?: '—'),
                    Placeholder::make('webhook_url')
                        ->label('Webhook callback URL')
                        ->content($webhookUrl),
                ])->columns(1),

            Section::make('Meta API credentials')
                ->description('From Meta Developer → WhatsApp → API Setup: Phone number ID, WhatsApp Business Account ID, and a permanent System User token.')
                ->schema([
                    Toggle::make('whatsapp_enabled')
                        ->label('Enable WhatsApp integration')
                        ->helperText('When off, OTP and broadcasts are not sent even if keys are saved.'),
                    TextInput::make('whatsapp_access_token')
                        ->label('Access token')
                        ->password()
                        ->revealable()
                        ->helperText('Leave blank to keep the current token.')
                        ->columnSpanFull(),
                    TextInput::make('whatsapp_phone_number_id')
                        ->label('Phone number ID')
                        ->required(fn ($get) => (bool) $get('whatsapp_enabled')),
                    TextInput::make('whatsapp_business_account_id')
                        ->label('WhatsApp Business Account ID (WABA)')
                        ->helperText('Required to sync / list message templates.'),
                    TextInput::make('whatsapp_app_id')
                        ->label('Meta App ID'),
                    TextInput::make('whatsapp_app_secret')
                        ->label('Meta App Secret')
                        ->password()
                        ->revealable()
                        ->helperText('Used to verify webhook signatures. Leave blank to keep current.'),
                    TextInput::make('whatsapp_webhook_verify_token')
                        ->label('Webhook verify token')
                        ->helperText('Any secret string you also paste into Meta webhook “Verify token”.'),
                    TextInput::make('whatsapp_graph_version')
                        ->label('Graph API version')
                        ->placeholder('v21.0'),
                ])->columns(2),

            Section::make('Approved templates from Meta')
                ->description('Templates are created and approved in Meta. Sync them here, then pick which ones TNF uses.')
                ->schema([
                    Placeholder::make('templates_table')
                        ->hiddenLabel()
                        ->content(fn (): HtmlString => new HtmlString(
                            view('filament.whatsapp-templates', [
                                'templates' => $templates,
                                'syncedAt' => $syncedAt,
                            ])->render()
                        ))
                        ->columnSpanFull(),
                ]),

            Section::make('Assign templates for TNF')
                ->description('Pick an APPROVED template from the last sync, or type the name manually. OTP body needs {{1}}; news/ePaper need title {{1}} and link {{2}}.')
                ->schema([
                    Select::make('pick_otp_template')
                        ->label('Pick approved OTP template')
                        ->options($pickOptions)
                        ->searchable()
                        ->dehydrated(false)
                        ->placeholder(count($pickOptions) ? 'Choose approved template…' : 'Sync templates first')
                        ->disabled(fn (): bool => $pickOptions === [])
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            $this->applyPickedTemplate($state, $set, 'whatsapp_otp_template', 'whatsapp_otp_template_lang');
                        }),
                    Select::make('pick_news_template')
                        ->label('Pick approved news template')
                        ->options($pickOptions)
                        ->searchable()
                        ->dehydrated(false)
                        ->placeholder(count($pickOptions) ? 'Choose approved template…' : 'Sync templates first')
                        ->disabled(fn (): bool => $pickOptions === [])
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            $this->applyPickedTemplate($state, $set, 'whatsapp_news_template', 'whatsapp_news_template_lang');
                        }),
                    Select::make('pick_epaper_template')
                        ->label('Pick approved ePaper template')
                        ->options($pickOptions)
                        ->searchable()
                        ->dehydrated(false)
                        ->placeholder(count($pickOptions) ? 'Choose approved template…' : 'Sync templates first')
                        ->disabled(fn (): bool => $pickOptions === [])
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            $this->applyPickedTemplate($state, $set, 'whatsapp_epaper_template', 'whatsapp_epaper_template_lang');
                        }),
                    TextInput::make('whatsapp_otp_template')->label('OTP template name'),
                    TextInput::make('whatsapp_otp_template_lang')->label('OTP language')->default('en'),
                    TextInput::make('whatsapp_news_template')->label('News alert template'),
                    TextInput::make('whatsapp_news_template_lang')->label('News language')->default('en'),
                    TextInput::make('whatsapp_epaper_template')->label('ePaper alert template'),
                    TextInput::make('whatsapp_epaper_template_lang')->label('ePaper language')->default('en'),
                ])->columns(2),

            Section::make('Auto-share on publish')
                ->description('Editors can still override with the checkbox on each News / ePaper form.')
                ->schema([
                    Toggle::make('whatsapp_on_news')
                        ->label('Default: send WhatsApp on news publish'),
                    Toggle::make('whatsapp_on_epaper')
                        ->label('Default: send WhatsApp on ePaper publish'),
                ]),
        ]);
    }

    protected function applyPickedTemplate(?string $state, Set $set, string $nameKey, string $langKey): void
    {
        if (! filled($state) || ! str_contains($state, '||')) {
            return;
        }

        [$name, $lang] = explode('||', $state, 2);
        $set($nameKey, $name);
        $set($langKey, $lang);
    }
}
