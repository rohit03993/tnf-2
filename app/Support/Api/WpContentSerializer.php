<?php

namespace App\Support\Api;

use App\Models\Article;
use App\Models\EpaperEdition;
use App\Models\Submission;
use App\Models\Video;
use App\Services\StorageUrl;

class WpContentSerializer
{
    /** @return array<string, mixed> */
    public static function article(Article $article): array
    {
        $article->loadMissing('featuredMedia');

        return [
            'id' => $article->id,
            'title' => $article->title,
            'excerpt' => $article->excerpt ?? '',
            'content' => $article->content,
            'date' => $article->published_at?->format('Y-m-d H:i:s'),
            'slug' => $article->slug,
            'author_id' => $article->author_id,
            'featured' => $article->featuredMedia?->url(),
        ];
    }

    /** @return array<string, mixed> */
    public static function video(Video $video): array
    {
        $video->loadMissing('featuredMedia');

        return [
            'id' => $video->id,
            'title' => $video->title,
            'excerpt' => $video->excerpt ?? '',
            'content' => $video->content ?? '',
            'date' => $video->published_at?->format('Y-m-d H:i:s'),
            'slug' => $video->slug,
            'author_id' => $video->author_id,
            'featured' => $video->featuredMedia?->url(),
            'embed_url' => $video->embed_url,
        ];
    }

    /** @return array<string, mixed> */
    public static function epaper(EpaperEdition $edition, bool $includeAccess = false): array
    {
        $edition->loadMissing('featuredMedia');

        $data = [
            'id' => $edition->id,
            'title' => $edition->title,
            'excerpt' => $edition->excerpt ?? '',
            'content' => $edition->content ?? '',
            'date' => $edition->published_at?->format('Y-m-d H:i:s'),
            'slug' => $edition->slug,
            'author_id' => $edition->author_id,
            'featured' => $edition->featuredMedia?->url(),
            'restricted' => $edition->restricted,
            'pdf_status' => $edition->pdf_status->value,
        ];

        if ($includeAccess) {
            $data['pages'] = $edition->pages_json['pages'] ?? [];
            $data['pdf_url'] = StorageUrl::publicAsset($edition->pdf_path);
        }

        return $data;
    }

    /** @return array<string, mixed> */
    public static function submission(Submission $submission): array
    {
        return [
            'id' => $submission->id,
            'title' => $submission->title,
            'category_id' => $submission->category_id,
            'category' => $submission->category?->name,
            'status' => $submission->displayStatus(),
            'rejection_reason' => $submission->rejection_reason,
            'promoted_article_id' => $submission->promoted_article_id,
            'created_at' => $submission->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
