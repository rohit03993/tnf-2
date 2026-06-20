<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Support\Embed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Video extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'author_id',
        'embed_url',
        'status',
        'featured_media_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
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
        return $this->belongsToMany(Category::class, 'video_category');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'video_tag');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function thumbnailUrl(): ?string
    {
        $uploaded = $this->featuredMedia?->url();

        if ($uploaded) {
            return $uploaded;
        }

        return Embed::previewImageUrl($this->embed_url);
    }

    public function isInstagramEmbed(): bool
    {
        return Embed::isInstagram($this->embed_url);
    }

    protected static function booted(): void
    {
        static::saving(function (Video $video) {
            if ($video->status === ContentStatus::Published && ! $video->published_at) {
                $video->published_at = now();
            }
        });

        static::saved(function (Video $video) {
            Cache::forget('homepage.data');
            \App\Services\PageCacheService::bump();
            \App\Services\ContentPublishService::handlePublishedVideo($video);
        });
        static::deleted(function () {
            Cache::forget('homepage.data');
            \App\Services\PageCacheService::bump();
        });
    }
}
