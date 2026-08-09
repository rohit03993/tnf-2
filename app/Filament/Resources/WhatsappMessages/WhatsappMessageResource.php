<?php

namespace App\Filament\Resources\WhatsappMessages;

use App\Enums\UserRole;
use App\Filament\Resources\WhatsappMessages\Pages\ListWhatsappMessages;
use App\Filament\Resources\WhatsappMessages\Pages\ViewWhatsappMessage;
use App\Models\WhatsappMessage;
use App\Support\PhoneNumber;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WhatsappMessageResource extends Resource
{
    protected static ?string $model = WhatsappMessage::class;

    protected static ?string $navigationLabel = 'Message log';

    protected static ?string $modelLabel = 'WhatsApp message';

    protected static ?string $pluralModelLabel = 'WhatsApp messages';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|\UnitEnum|null $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        // Conversation inbox lives on WhatsAppInboxPage; keep this as a raw log if needed.
        return false;
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Editor, UserRole::Admin], true);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = WhatsappMessage::unreadInboundCount();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Message')->schema([
                TextInput::make('phone')
                    ->formatStateUsing(fn (?string $state) => PhoneNumber::formatDisplay($state) ?? $state)
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('direction')->disabled()->dehydrated(false),
                TextInput::make('type')->disabled()->dehydrated(false),
                TextInput::make('status')->disabled()->dehydrated(false),
                TextInput::make('template_name')->disabled()->dehydrated(false),
                Placeholder::make('when')
                    ->label('When')
                    ->content(fn (?WhatsappMessage $record) => $record?->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? '—'),
                Textarea::make('body')->disabled()->dehydrated(false)->rows(6)->columnSpanFull(),
                Textarea::make('error_message')->disabled()->dehydrated(false)->rows(2)->columnSpanFull()
                    ->visible(fn (?WhatsappMessage $record) => filled($record?->error_message)),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('direction')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'inbound' ? 'success' : 'gray'),
                TextColumn::make('phone')
                    ->label('Number')
                    ->formatStateUsing(fn (?string $state) => PhoneNumber::formatDisplay($state) ?? $state)
                    ->searchable()
                    ->copyable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('—'),
                TextColumn::make('body')
                    ->label('Message')
                    ->limit(60)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('type')->toggleable(),
                TextColumn::make('status')->badge()->toggleable(),
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('read_at')
                    ->label('Inbox')
                    ->formatStateUsing(fn ($state, WhatsappMessage $record): string => $record->direction === 'inbound'
                        ? (filled($record->read_at) ? 'Read' : 'New')
                        : '—')
                    ->badge()
                    ->color(fn ($state, WhatsappMessage $record): string => $record->direction === 'inbound' && blank($record->read_at)
                        ? 'danger'
                        : 'gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('direction')
                    ->options([
                        'inbound' => 'Inbound',
                        'outbound' => 'Outbound',
                    ]),
                TernaryFilter::make('unread')
                    ->label('Unread inbound')
                    ->queries(
                        true: fn (Builder $query) => $query->where('direction', 'inbound')->whereNull('read_at'),
                        false: fn (Builder $query) => $query->where('direction', 'inbound')->whereNotNull('read_at'),
                    ),
            ])
            ->recordUrl(fn (WhatsappMessage $record) => static::getUrl('view', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsappMessages::route('/'),
            'view' => ViewWhatsappMessage::route('/{record}'),
        ];
    }
}
