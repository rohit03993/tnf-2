<?php

namespace App\Support;

use App\Models\Article;
use Illuminate\Support\Str;

class ArticleSlug
{
    public static function uniqueFromTitle(string $title, ?int $fallbackId = null): string
    {
        $slug = Str::slug($title);

        if ($slug === '') {
            $slug = $fallbackId ? 'story-'.$fallbackId : 'story-'.Str::lower(Str::random(8));
        }

        $base = $slug;
        $candidate = $base;
        $counter = 1;

        while (Article::query()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
