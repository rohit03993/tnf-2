<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Enums\SubmissionStatus;
use App\Models\Article;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\SubmissionApprovedNotification;
use App\Notifications\SubmissionRejectedNotification;
use App\Support\ArticleSlug;
use Illuminate\Validation\ValidationException;

class SubmissionWorkflowService
{
    /** @param  array<int>  $categoryIds */
    public static function approve(Submission $submission, User $reviewer, array $categoryIds = []): Article
    {
        if ($categoryIds === [] && $submission->category_id) {
            $categoryIds = [$submission->category_id];
        }

        if ($categoryIds === []) {
            throw ValidationException::withMessages([
                'categories' => 'Select at least one category before approving.',
            ]);
        }

        $submission->refresh();

        $slug = ArticleSlug::uniqueFromTitle($submission->title, $submission->id);

        $article = Article::query()->create([
            'title' => $submission->title,
            'content' => $submission->content,
            'excerpt' => str($submission->content)->stripTags()->limit(200)->toString(),
            'author_id' => $submission->user_id ?? $reviewer->id,
            'status' => ContentStatus::Published,
            'embed_url' => $submission->embed_url,
            'featured_media_id' => $submission->featured_media_id,
            'slug' => $slug,
            'published_at' => now(),
        ]);

        $article->categories()->sync($categoryIds);

        $submission->update([
            'status' => SubmissionStatus::Approved,
            'promoted_article_id' => $article->id,
        ]);

        $submission->user?->notify(new SubmissionApprovedNotification($submission->fresh(), $article));

        return $article;
    }

    public static function reject(Submission $submission, ?string $reason = null): void
    {
        $submission->update([
            'status' => SubmissionStatus::Rejected,
            'rejection_reason' => $reason,
        ]);

        $submission->user?->notify(new SubmissionRejectedNotification($submission->fresh()));
    }
}
