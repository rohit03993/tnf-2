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
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
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
                ->description('Pick an APPROVED Meta template. Map variables on the Templates page if needed.')
                ->schema([
                    TextInput::make('name')
                        ->label('Campaign name')
                        ->required()
                        ->maxLength(255)
                        ->default(fn (): string => now()->format('Y-m-d').'-'.str_pad((string) (WhatsappCampaign::query()->whereDate('created_at', today())->count() + 1), 3, '0', STR_PAD_LEFT)),
                    Select::make('whatsapp_template_id')
                        ->label('WhatsApp template')
                        ->options(fn (): array => WhatsappTemplate::query()
                            ->where('status', 'APPROVED')
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (WhatsappTemplate $t): array => [$t->id => $t->label()])
                            ->all())
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('template_manual_params', [])),
                    Placeholder::make('template_preview')
                        ->label('Template body')
                        ->content(function (Get $get): HtmlString {
                            $id = $get('whatsapp_template_id');
                            if (! filled($id)) {
                                return new HtmlString('<span class="text-gray-500">Select a template…</span>');
                            }
                            $template = WhatsappTemplate::query()->find($id);

                            return new HtmlString(
                                '<div class="whitespace-pre-wrap rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm">'
                                .e($template?->body ?: '—')
                                .'</div>'
                            );
                        })
                        ->visible(fn (Get $get): bool => filled($get('whatsapp_template_id')))
                        ->columnSpanFull(),
                ]),
            Section::make('2. Audience')
                ->description('Who receives this campaign.')
                ->schema([
                    Select::make('audience_type')
                        ->label('Send to')
                        ->options([
                            WhatsAppAudienceType::OptedIn->value => WhatsAppAudienceType::OptedIn->label(),
                        ])
                        ->default(WhatsAppAudienceType::OptedIn->value)
                        ->required()
                        ->live(),
                    Placeholder::make('audience_estimate')
                        ->label('Estimated recipients')
                        ->content(function (Get $get): string {
                            $count = app(WhatsAppCampaignService::class)->estimateAudienceCount([
                                'audience_type' => $get('audience_type'),
                            ]);

                            return $count.' user(s) with WhatsApp opt-in + phone';
                        }),
                ]),
            Section::make('3. Campaign content')
                ->description('Values used for template variables ({{1}}, {{2}}, …).')
                ->schema([
                    TextInput::make('campaign_variables.title')
                        ->label('Title / headline')
                        ->required()
                        ->maxLength(60)
                        ->helperText('Usually fills {{1}}'),
                    TextInput::make('campaign_variables.url')
                        ->label('Link URL')
                        ->required()
                        ->url()
                        ->helperText('Usually fills {{2}} — use full https URL'),
                    Repeater::make('template_manual_params')
                        ->label('Extra manual variables')
                        ->schema([
                            TextInput::make('value')->label('Value')->required(),
                        ])
                        ->default([])
                        ->helperText('Only if the template mapping uses Manual slots beyond title/url.')
                        ->columnSpanFull(),
                ]),
            Section::make('4. Send')
                ->schema([
                    Toggle::make('send_immediately')
                        ->label('Send immediately after create')
                        ->default(true)
                        ->dehydrated(false),
                ]),
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
                TextEntry::make('campaign_variables.title')->label('Title'),
                TextEntry::make('campaign_variables.url')->label('URL'),
                TextEntry::make('started_at')->dateTime(),
                TextEntry::make('finished_at')->dateTime(),
            ])->columns(2),
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
