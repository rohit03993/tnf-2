<?php

namespace App\Filament\Resources\ContactMessages;

use App\Enums\UserRole;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Models\ContactMessage;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationLabel = 'Contact Messages';

    protected static ?string $modelLabel = 'contact message';

    protected static ?string $pluralModelLabel = 'contact messages';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ContactMessage::query()->whereNull('read_at')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sender')->schema([
                TextInput::make('name')->disabled()->dehydrated(false),
                TextInput::make('email')->disabled()->dehydrated(false),
                TextInput::make('phone')->disabled()->dehydrated(false),
                Placeholder::make('submitted_at')
                    ->label('Submitted at')
                    ->content(fn (?ContactMessage $record) => $record?->created_at?->format('M j, Y g:i A') ?? '—'),
            ])->columns(2),
            Section::make('Message')->schema([
                TextInput::make('subject')->disabled()->dehydrated(false)->columnSpanFull(),
                Textarea::make('message')->disabled()->dehydrated(false)->rows(8)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('phone')->placeholder('—'),
                TextColumn::make('subject')->searchable()->limit(40),
                TextColumn::make('created_at')->label('Received')->dateTime()->sortable(),
                TextColumn::make('read_at')
                    ->label('Status')
                    ->formatStateUsing(fn (?string $state) => filled($state) ? 'Read' : 'New')
                    ->badge()
                    ->color(fn (?string $state) => filled($state) ? 'gray' : 'danger'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('unread')
                    ->label('Unread only')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('read_at'),
                        false: fn (Builder $query) => $query->whereNotNull('read_at'),
                    ),
            ])
            ->recordUrl(fn (ContactMessage $record) => static::getUrl('view', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
            'view' => ViewContactMessage::route('/{record}'),
        ];
    }
}
