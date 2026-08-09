<?php

namespace App\Filament\Resources\WhatsappLiveCampaigns;

use App\Enums\UserRole;
use App\Enums\WhatsAppLiveCampaignStatus;
use App\Filament\Resources\WhatsappLiveCampaigns\Pages\CreateWhatsappLiveCampaign;
use App\Filament\Resources\WhatsappLiveCampaigns\Pages\EditWhatsappLiveCampaign;
use App\Filament\Resources\WhatsappLiveCampaigns\Pages\ListWhatsappLiveCampaigns;
use App\Models\WhatsappLiveCampaign;
use App\Models\WhatsappTemplate;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class WhatsappLiveCampaignResource extends Resource
{
    protected static ?string $model = WhatsappLiveCampaign::class;

    protected static ?string $navigationLabel = 'Live campaigns';

    protected static ?string $modelLabel = 'Live campaign';

    protected static ?string $pluralModelLabel = 'Live campaigns';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|\UnitEnum|null $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Editor, UserRole::Admin], true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Campaign')
                ->description('Create a named live campaign linked to an APPROVED template. After Go live, use it for OTP, news, ePaper, and other system sends.')
                ->schema([
                    TextInput::make('name')
                        ->label('Campaign name')
                        ->required()
                        ->maxLength(120)
                        ->unique(ignoreRecord: true)
                        ->helperText('e.g. tnf_login_otp, tnf_news_alert — pick this name in WhatsApp API settings.'),
                    Select::make('whatsapp_template_id')
                        ->label('Linked Meta template')
                        ->options(fn (): array => WhatsappTemplate::query()
                            ->where('status', 'APPROVED')
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (WhatsappTemplate $template): array => [
                                $template->id => $template->name.' ('.$template->language.', '.$template->param_count.' param'
                                    .((int) $template->param_count === 1 ? '' : 's').')',
                            ])
                            ->all())
                        ->searchable()
                        ->required()
                        ->native(false)
                        ->disabled(fn (?WhatsappLiveCampaign $record): bool => $record?->isLive() ?? false)
                        ->helperText('Only APPROVED templates. Pause the campaign to change the template.'),
                    Textarea::make('description')
                        ->rows(2)
                        ->maxLength(500)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('How to use')
                ->schema([
                    Placeholder::make('usage')
                        ->label('')
                        ->content(new HtmlString(
                            '<div class="space-y-2 text-sm text-gray-600">'
                            .'<ol class="list-decimal space-y-1 pl-4">'
                            .'<li>Save this campaign, then click <strong>Go live</strong>.</li>'
                            .'<li>Open <strong>Settings → WhatsApp API</strong> and choose this live campaign for OTP / News / ePaper.</li>'
                            .'<li>System features will send using the linked approved template.</li>'
                            .'</ol></div>'
                        ))
                        ->columnSpanFull(),
                ])
                ->collapsed()
                ->visible(fn (?WhatsappLiveCampaign $record): bool => $record !== null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (WhatsappLiveCampaign $record): ?string => $record->template?->name),
                TextColumn::make('template.language')->label('Lang')->toggleable(),
                TextColumn::make('template.param_count')->label('Params')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (WhatsAppLiveCampaignStatus $state): string => $state->label())
                    ->color(fn (WhatsAppLiveCampaignStatus $state): string => match ($state) {
                        WhatsAppLiveCampaignStatus::Live => 'success',
                        WhatsAppLiveCampaignStatus::Draft => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('went_live_at')->label('Live since')->dateTime('M j, Y')->sortable()->toggleable(),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'live' => 'Live',
                    'draft' => 'Draft',
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsappLiveCampaigns::route('/'),
            'create' => CreateWhatsappLiveCampaign::route('/create'),
            'edit' => EditWhatsappLiveCampaign::route('/{record}/edit'),
        ];
    }
}
