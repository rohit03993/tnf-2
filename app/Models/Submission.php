<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'embed_url',
        'featured_media_id',
        'category_id',
        'status',
        'rejection_reason',
        'promoted_article_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function promotedArticle(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'promoted_article_id');
    }

    public function isLive(): bool
    {
        return $this->status === SubmissionStatus::Approved
            && $this->promoted_article_id
            && $this->promotedArticle?->status === ContentStatus::Published;
    }

    public function isRemoved(): bool
    {
        return $this->status === SubmissionStatus::Approved && ! $this->promoted_article_id;
    }

    public function canWithdraw(): bool
    {
        if ($this->status === SubmissionStatus::Pending) {
            return true;
        }

        if ($this->status === SubmissionStatus::Rejected) {
            return true;
        }

        return $this->isRemoved();
    }

    public function displayStatus(): string
    {
        if ($this->isRemoved()) {
            return 'Article removed';
        }

        return $this->status->label();
    }

    public function statusBadgeClass(): string
    {
        if ($this->isRemoved()) {
            return 'bg-tnf-gray-dark text-tnf-navy';
        }

        return $this->status->badgeClass();
    }
}

