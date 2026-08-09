<?php

namespace App\Filament\Resources\WhatsappCampaigns;

use App\Enums\UserRole;
use App\Enums\WhatsAppAudienceType;
use App\Enums\WhatsAppCampaignStatus;
use App\Filament\Resources\WhatsappCampaigns\Pages\CreateWhatsappCampaign;
use App\Filament\Resources\WhatsappCampaigns\Pages\ListWhatsappCampaigns;
use App\Filament\Resources\WhatsappCampaigns\Pages\ViewWhatsappCampaign;
use App\Filament\Resources\WhatsappCampaigns\RelationManagers\RecipientsRelationManager;
use App\Models\WhatsappCampaign;
use App\Models\WhatsappTemplate;
use App\Services\WhatsAppCampaignService;
use App\Support\WhatsAppCampaignFormHelper;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class WhatsappCampaignResource extends Resource
{
    protected static ?string $model = WhatsappCampaign::class;

    protected static ?string $navigationLabel = 'Campaigns';

    protected static ?string $modelLabel = 'WhatsApp campaign';

    protected static ?string $pluralModelLabel = 'WhatsApp campaigns';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|\UnitEnum|null $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Editor, UserRole::Admin], true);
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('1. Template')
                ->description('Choose the WhatsApp message to send. Per-user fields are filled automatically for each recipient.')
                ->schema([
                    TextInput::make('name')
                        ->label('Campaign name')
                        ->required()
                        ->maxLength(255)
                        ->default(fn (): string => WhatsAppCampaignFormHelper::generateDefaultName())
                        ->helperText('Auto-generated as YYYY-MM-DD-001. Edit if needed.'),
                    Select::make('whatsapp_template_id')
                        ->label('WhatsApp template')
                        ->options(function (): array {
                            try {
                                return WhatsappTemplate::query()
                                    ->where('status', 'APPROVED')
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (WhatsappTemplate $t): array => [$t->id => $t->label()])
                                    ->all();
                            } catch (\Throwable) {
                                return [];
                            }
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false)
                        ->live()
                        ->dehydrateStateUsing(fn ($state): ?int => filled($state) ? (int) $state : null)
                        ->afterStateUpdated(function ($state, callable $set): void {
                            $set('template_manual_params', []);
                            $set('campaign_variables', WhatsAppCampaignFormHelper::defaultCampaignVariables(
                                filled($state) ? (int) $state : null,
                            ));
                        })
                        ->placeholder('Choose a template…'),
                    Placeholder::make('template_preview_card')
                        ->label('')
                        ->content(fn (Get $get): HtmlString => WhatsAppCampaignFormHelper::renderTemplatePreviewCard(
                            filled($get('whatsapp_template_id')) ? (int) $get('whatsapp_template_id') : null,
                        ))
                        ->visible(fn (Get $get): bool => filled($get('whatsapp_template_id')))
                        ->columnSpanFull(),
                ])
                ->columns(1),
            Section::make('2. Audience')
                ->description('Pick who receives this campaign. Only users with a phone number are included.')
                ->schema([
                    Select::make('audience_type')
                        ->label('Send to')
                        ->options([
                            WhatsAppAudienceType::OptedIn->value => WhatsAppAudienceType::OptedIn->label(),
                        ])
                        ->default(WhatsAppAudienceType::OptedIn->value)
                        ->required()
                        ->native(false)
                        ->live(),
                    Placeholder::make('estimated_recipients')
                        ->label('')
                        ->content(function (Get $get): HtmlString {
                            $count = app(WhatsAppCampaignService::class)->estimateAudienceCount([
                                'audience_type' => $get('audience_type'),
                            ]);

                            return WhatsAppCampaignFormHelper::renderRecipientEstimate($count);
                        })
                        ->columnSpanFull(),
                ])
                ->visible(fn (Get $get): bool => filled($get('whatsapp_template_id'))),
            Section::make('3. Campaign details')
                ->description('Fill only the fields this template needs. They apply to every message in this campaign.')
                ->schema(fn (Get $get): array => WhatsAppCampaignFormHelper::messageDetailFields(
                    filled($get('whatsapp_template_id')) ? (int) $get('whatsapp_template_id') : null,
                ))
                ->columns(2)
                ->visible(fn (Get $get): bool => filled($get('whatsapp_template_id'))),
            Section::make('4. Send')
                ->description('Review, then create the campaign. Large sends may take a few minutes.')
                ->schema([
                    Toggle::make('send_immediately')
                        ->label('Send immediately after create')
                        ->helperText('When off, the campaign is saved as draft — start it later from the campaign view.')
                        ->default(true)
                        ->dehydrated(false)
                        ->inline(false),
                ])
                ->visible(fn (Get $get): bool => filled($get('whatsapp_template_id'))),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Campaign')->schema([
                TextEntry::make('name'),
                TextEntry::make('status')->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof WhatsAppCampaignStatus ? $state->label() : (string) $state)
                    ->color(fn ($state): string => match ($state instanceof WhatsAppCampaignStatus ? $state : WhatsAppCampaignStatus::tryFrom((string) $state)) {
                        WhatsAppCampaignStatus::Completed => 'success',
                        WhatsAppCampaignStatus::Running, WhatsAppCampaignStatus::Queued => 'warning',
                        WhatsAppCampaignStatus::Paused => 'danger',
                        default => 'gray',
                    }),
                TextEntry::make('template.label')->label('Template'),
                TextEntry::make('audience_type')
                    ->formatStateUsing(fn ($state) => $state instanceof WhatsAppAudienceType ? $state->label() : (string) $state),
                TextEntry::make('total_recipients')->label('Recipients'),
                TextEntry::make('sent_count')->label('Sent'),
                TextEntry::make('failed_count')->label('Failed'),
                TextEntry::make('started_at')->dateTime(),
                TextEntry::make('finished_at')->dateTime(),
            ])->columns(2),
            Section::make('Message preview')
                ->schema([
                    TextEntry::make('resolved_message_preview')
                        ->label('')
                        ->state(function (WhatsappCampaign $record): ?string {
                            $sent = $record->recipients()
                                ->whereNotNull('message_sent')
                                ->where('message_sent', '!=', '')
                                ->orderBy('id')
                                ->value('message_sent');

                            return filled($sent) ? (string) $sent : $record->template?->body;
                        })
                        ->columnSpanFull(),
                ])
                ->collapsed()
                ->visible(fn (WhatsappCampaign $record): bool => filled($record->template?->body)
                    || $record->recipients()->whereNotNull('message_sent')->exists()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->limit(40),
                TextColumn::make('template.name')->label('Template')->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (WhatsAppCampaignStatus $state): string => $state->label())
                    ->color(fn (WhatsAppCampaignStatus $state): string => match ($state) {
                        WhatsAppCampaignStatus::Completed => 'success',
                        WhatsAppCampaignStatus::Running, WhatsAppCampaignStatus::Queued => 'warning',
                        WhatsAppCampaignStatus::Paused => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('total_recipients')->label('Total'),
                TextColumn::make('sent_count')->label('Sent'),
                TextColumn::make('failed_count')->label('Failed'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordUrl(fn (WhatsappCampaign $record) => static::getUrl('view', ['record' => $record]));
    }

    public static function getRelations(): array
    {
        return [
            RecipientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsappCampaigns::route('/'),
            'create' => CreateWhatsappCampaign::route('/create'),
            'view' => ViewWhatsappCampaign::route('/{record}'),
        ];
    }
}
