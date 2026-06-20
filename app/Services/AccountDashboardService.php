<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Collection;

class AccountDashboardService
{
    /** @return array{total: int, live: int, removed: int, pending: int, rejected: int} */
    public static function kpis(User $user): array
    {
        $submissions = $user->submissions()->with('promotedArticle')->get();

        return [
            'total' => $submissions->count(),
            'live' => $submissions->filter(fn (Submission $s) => $s->isLive())->count(),
            'removed' => $submissions->filter(fn (Submission $s) => $s->isRemoved())->count(),
            'pending' => $submissions->where('status', SubmissionStatus::Pending)->count(),
            'rejected' => $submissions->where('status', SubmissionStatus::Rejected)->count(),
        ];
    }

    /** @return Collection<int, Submission> */
    public static function submissions(User $user): Collection
    {
        return $user->submissions()
            ->with(['promotedArticle', 'featuredMedia', 'category'])
            ->latest()
            ->get();
    }
}
