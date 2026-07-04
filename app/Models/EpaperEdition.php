<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\PdfStatus;
use App\Services\ContentCacheService;
use App\Services\ContentPublishService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EpaperEdition extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'author_id',
        'pdf_path',
        'restricted',
        'pdf_status',
        'pdf_job_id',
        'pdf_error',
        'pages_json',
        'featured_media_id',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'restricted' => 'boolean',
            'pdf_status' => PdfStatus::class,
            'status' => ContentStatus::class,
            'pages_json' => 'array',
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

    public function coverImageUrl(): ?string
    {
        return \App\Services\EpaperViewerService::coverImageUrl($this);
    }

    public function pdfPublicUrl(): ?string
    {
        return \App\Services\EpaperViewerService::pdfUrl($this);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    protected static function booted(): void
    {
        static::saving(function (EpaperEdition $edition) {
            if ($edition->status === ContentStatus::Published && $edition->published_at === null) {
                $edition->published_at = now();
            }
        });

        static::saved(function (EpaperEdition $edition) {
            ContentCacheService::bust();
            ContentPublishService::handlePublishedEpaper($edition);
        });
        static::deleted(function () {
            ContentCacheService::bust();
        });
    }
}
