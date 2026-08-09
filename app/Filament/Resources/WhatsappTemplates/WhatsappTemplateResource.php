<?php

namespace App\Filament\Resources\WhatsappTemplates;

use App\Enums\UserRole;
use App\Filament\Resources\WhatsappTemplates\Pages\CreateWhatsappTemplate;
use App\Filament\Resources\WhatsappTemplates\Pages\ListWhatsappTemplates;
use App\Filament\Resources\WhatsappTemplates\Pages\ViewWhatsappTemplate;
use App\Models\WhatsappTemplate;
use App\Support\WhatsAppTemplateBuilder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class WhatsappTemplateResource extends Resource
{
    protected static ?string $model = WhatsappTemplate::class;

    protected static ?string $navigationLabel = 'WhatsApp Templates';

    protected static ?string $modelLabel = 'WhatsApp template';

    protected static ?string $pluralModelLabel = 'WhatsApp templates';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 2;

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
            Section::make('Submit to Meta')
                ->description('Creates a template on your WhatsApp Business account. Meta usually approves within minutes to 24 hours — use Sync on the list page to refresh status.')
                ->schema(static::createFormSchema())
                ->visible(fn (?WhatsappTemplate $record): bool => $record === null),
            Section::make('Template details')
                ->schema(static::viewFormSchema())
                ->visible(fn (?WhatsappTemplate $record): bool => $record !== null),
        ]);
    }

    /**
     * @return list<\Filament\Forms\Components\Component>
     */
    protected static function createFormSchema(): array
    {
        return [
            Placeholder::make('guide')
                ->hiddenLabel()
                ->content(new HtmlString(
                    '<div class="rounded-xl border border-amber-200/70 bg-amber-50/50 px-4 py-3 text-sm">'
                    .'<p class="font-semibold">Tips for TNF Today</p>'
                    .'<ul class="mt-1 list-disc space-y-1 pl-4 text-xs text-gray-600">'
                    .'<li>OTP / login codes → category <strong>Authentication</strong></li>'
                    .'<li>News &amp; ePaper alerts → category <strong>Marketing</strong> or <strong>Utility</strong></li>'
                    .'<li>Use <code>{{1}}</code>, <code>{{2}}</code> for variables and fill sample values below for Meta review</li>'
                    .'</ul></div>'
                ))
                ->columnSpanFull(),
            TextInput::make('name')
                ->label('Template name')
                ->required()
                ->maxLength(64)
                ->helperText('Lowercase letters, numbers, underscores only (e.g. tnf_login_otp).')
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
                    'name',
                    WhatsAppTemplateBuilder::normalizeName((string) $state),
                )),
            Select::make('language')
                ->options([
                    'en' => 'English (en)',
                    'en_US' => 'English US (en_US)',
                    'hi' => 'Hindi (hi)',
                ])
                ->default('en')
                ->required(),
            Select::make('category')
                ->options([
                    'UTILITY' => 'Utility',
                    'MARKETING' => 'Marketing',
                    'AUTHENTICATION' => 'Authentication (OTP)',
                ])
                ->default('UTILITY')
                ->required(),
            TextInput::make('header_text')
                ->label('Header (optional)')
                ->maxLength(60)
                ->helperText('Plain text only — no variables.'),
            Textarea::make('body_text')
                ->label('Message body')
                ->required()
                ->rows(8)
                ->helperText('Example: Your TNF Today code is {{1}}. Valid for 10 minutes.')
                ->live(debounce: 400)
                ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                    $set(
                        'body_variable_samples',
                        static::syncSampleRows((string) $state, $get('body_variable_samples') ?? []),
                    );
                })
                ->columnSpanFull(),
            Section::make('Template variables')
                ->description('Meta requires one sample value per {{n}} for approval.')
                ->schema([
                    Repeater::make('body_variable_samples')
                        ->label('')
                        ->schema([
                            TextInput::make('index')->hidden()->dehydrated(),
                            TextInput::make('example')
                                ->label(fn (Get $get): string => '{{'.$get('index').'}} sample')
                                ->required()
                                ->maxLength(256),
                        ])
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->columnSpanFull(),
                    Placeholder::make('body_preview')
                        ->label('Preview with sample values')
                        ->content(function (Get $get): HtmlString {
                            $preview = static::previewBody(
                                (string) $get('body_text'),
                                $get('body_variable_samples') ?? [],
                            );

                            return new HtmlString(
                                '<div class="whitespace-pre-wrap rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-800">'
                                .e($preview)
                                .'</div>'
                            );
                        })
                        ->columnSpanFull(),
                ])
                ->visible(fn (Get $get): bool => count(WhatsAppTemplateBuilder::placeholderOrder((string) $get('body_text'))) > 0)
                ->columnSpanFull(),
            TextInput::make('footer_text')
                ->label('Footer (optional)')
                ->maxLength(60),
            Toggle::make('allow_category_change')
                ->label('Allow Meta to recategorize')
                ->default(true)
                ->dehydrated(false)
                ->helperText('Recommended — Meta may adjust UTILITY vs MARKETING during review.'),
        ];
    }

    /**
     * @return list<\Filament\Forms\Components\Component>
     */
    protected static function viewFormSchema(): array
    {
        return [
            Placeholder::make('status_display')
                ->label('Status')
                ->content(fn (WhatsappTemplate $record): HtmlString => new HtmlString(
                    '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset '
                    .match (strtoupper((string) $record->status)) {
                        'APPROVED' => 'bg-success-50 text-success-700 ring-success-600/20',
                        'PENDING' => 'bg-warning-50 text-warning-700 ring-warning-600/20',
                        'REJECTED' => 'bg-danger-50 text-danger-700 ring-danger-600/20',
                        default => 'bg-gray-50 text-gray-700 ring-gray-500/20',
                    }
                    .'">'.e((string) $record->status).'</span>'
                )),
            TextInput::make('name')->disabled(),
            TextInput::make('language')->disabled(),
            TextInput::make('category')->disabled(),
            Textarea::make('body')->label('Message body')->disabled()->rows(5)->columnSpanFull(),
            Toggle::make('is_active')->label('Active for sending')->disabled(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('language')->label('Lang')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtoupper($state)) {
                        'APPROVED' => 'success',
                        'PENDING' => 'warning',
                        'REJECTED' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('category')->toggleable(),
                TextColumn::make('param_count')->label('Params')->sortable(),
                TextColumn::make('body')->limit(50)->toggleable(),
                TextColumn::make('synced_at')->label('Last sync')->dateTime('M j, Y g:i A')->sortable(),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'APPROVED' => 'Approved',
                    'PENDING' => 'Pending',
                    'REJECTED' => 'Rejected',
                ]),
            ])
            ->defaultSort('name')
            ->recordUrl(fn (WhatsappTemplate $record) => static::getUrl('view', ['record' => $record]));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeCreateFormData(array $data): array
    {
        $samples = $data['body_variable_samples'] ?? [];

        if (is_array($samples) && $samples !== []) {
            $data['body_examples'] = collect($samples)
                ->sortBy(fn (array $row) => (int) ($row['index'] ?? 0))
                ->pluck('example')
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->implode(',');
        }

        unset($data['body_variable_samples'], $data['allow_category_change']);

        return $data;
    }

    /**
     * @param  list<array<string, mixed>>  $existing
     * @return list<array{index: int, example: string}>
     */
    public static function syncSampleRows(string $body, array $existing): array
    {
        $byIndex = collect($existing)->keyBy(fn ($row) => (int) ($row['index'] ?? 0));

        return collect(WhatsAppTemplateBuilder::placeholderOrder($body))
            ->map(fn (int $index): array => [
                'index' => $index,
                'example' => (string) ($byIndex->get($index)['example'] ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $samples
     */
    public static function previewBody(string $body, array $samples): string
    {
        $preview = $body;

        foreach ($samples as $row) {
            $index = (int) ($row['index'] ?? 0);
            $example = trim((string) ($row['example'] ?? ''));
            if ($index < 1) {
                continue;
            }
            $preview = str_replace('{{'.$index.'}}', $example !== '' ? $example : '['.$index.']', $preview);
        }

        return $preview !== '' ? $preview : '—';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsappTemplates::route('/'),
            'create' => CreateWhatsappTemplate::route('/create'),
            'view' => ViewWhatsappTemplate::route('/{record}'),
        ];
    }
}
