<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }

    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'video_category');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function isInUse(): bool
    {
        if ($this->relationLoaded('articles_count')) {
            return ($this->articles_count ?? 0) > 0
                || ($this->videos_count ?? 0) > 0
                || ($this->submissions_count ?? 0) > 0;
        }

        return $this->articles()->exists()
            || $this->videos()->exists()
            || $this->submissions()->exists();
    }
}
