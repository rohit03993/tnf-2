<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LatestNewsWidget extends Widget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.latest-news-widget';

    public static function canView(): bool
    {
        return auth()->user()?->role->canAccessAdmin() ?? false;
    }

    /** @return Collection<int, Article> */
    public function getArticles(): Collection
    {
        return $this->articleQuery()->limit(5)->get();
    }

    public function getViewAllUrl(): string
    {
        return ArticleResource::getUrl('index');
    }

    public function getEditUrl(Article $article): string
    {
        return ArticleResource::getUrl('edit', ['record' => $article]);
    }

    /** @return Builder<Article> */
    protected function articleQuery(): Builder
    {
        $query = Article::query()->with(['author', 'featuredMedia']);

        if (auth()->user()?->role === UserRole::Author) {
            $query->where('author_id', auth()->id());
        }

        return $query->latest('updated_at');
    }
}
