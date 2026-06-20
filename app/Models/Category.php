<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
}
