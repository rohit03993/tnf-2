<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Article extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'author_id',
        'status',
        'embed_url',
        'featured_media_id',
        'comment_count',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'comment_count' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function submission(): HasOne
    {
        return $this->hasOne(Submission::class, 'promoted_article_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeTrending(Builder $query): Builder
    {
        return $query->published()->orderByDesc('comment_count');
    }

    public function scopeInCategory(Builder $query, string $slug): Builder
    {
        return $query->whereHas('categories', fn (Builder $q) => $q->where('slug', $slug));
    }

    protected static function booted(): void
    {
        static::saved(function (Article $article) {
            Cache::forget('homepage.data');
            \App\Services\PageCacheService::bump();
            \App\Services\ContentPublishService::handlePublishedArticle($article);
        });
        static::deleted(function () {
            Cache::forget('homepage.data');
            \App\Services\PageCacheService::bump();
        });
    }
}
