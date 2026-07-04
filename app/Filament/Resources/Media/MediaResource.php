<?php

namespace App\Filament\Resources\Media;

use App\Enums\UserRole;
use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Models\Media;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static ?string $navigationLabel = 'Media Library';

    protected static ?string $modelLabel = 'media file';

    protected static ?string $pluralModelLabel = 'media library';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|\UnitEnum|null $navigationGroup = 'Library';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Editor, UserRole::Admin], true);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Placeholder::make('preview')
                    ->label('Preview')
                    ->content(function (?Media $record): HtmlString|string {
                        $url = $record?->url();

                        if (! $url || ! str_starts_with((string) $record?->mime, 'image/')) {
                            return 'No image preview available.';
                        }

                        return new HtmlString(
                            '<img src="'.e($url).'" alt="'.e($record->alt ?? '').'" class="max-h-72 rounded-lg border border-gray-200">'
                        );
                    }),
                TextInput::make('path')->disabled()->dehydrated(false),
                TextInput::make('mime')->label('MIME type')->disabled()->dehydrated(false),
                TextInput::make('size')
                    ->label('File size')
                    ->formatStateUsing(fn (?Media $record) => $record?->humanSize())
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('alt')
                    ->label('Alt text')
                    ->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path')
                    ->label('Preview')
                    ->disk(fn (Media $record) => $record->disk)
                    ->visibility('public')
                    ->square()
                    ->checkFileExistence(false),
                TextColumn::make('path')->searchable()->limit(40),
                TextColumn::make('mime')->label('Type')->toggleable(),
                TextColumn::make('size')->label('Size')->formatStateUsing(fn (Media $record) => $record->humanSize()),
                TextColumn::make('alt')->limit(20)->placeholder('—'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('mime')
                    ->label('Type')
                    ->options([
                        'image/jpeg' => 'JPEG',
                        'image/png' => 'PNG',
                        'image/webp' => 'WebP',
                        'image/gif' => 'GIF',
                        'application/pdf' => 'PDF',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }

    public static function deleteMedia(Media $media): void
    {
        try {
            $media->deleteWithFile();
        } catch (\RuntimeException $exception) {
            Notification::make()
                ->title('Cannot delete file')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()->title('Media file deleted')->success()->send();
    }
}
