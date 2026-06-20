<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestNewsWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->role->canAccessAdmin() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Latest news')
            ->query($this->articleQuery())
            ->columns([
                TextColumn::make('title')
                    ->limit(50)
                    ->url(fn (Article $record) => ArticleResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('author.name')->label('Author'),
                TextColumn::make('status')->badge(),
                TextColumn::make('published_at')->dateTime()->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5]);
    }

    /** @return Builder<Article> */
    protected function articleQuery(): Builder
    {
        $query = Article::query()->with('author');

        if (auth()->user()?->role === UserRole::Author) {
            $query->where('author_id', auth()->id());
        }

        return $query->latest('updated_at');
    }
}
