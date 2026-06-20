<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Editor, UserRole::Admin], true);
    }

    public function view(User $user, Submission $submission): bool
    {
        if (in_array($user->role, [UserRole::Editor, UserRole::Admin], true)) {
            return true;
        }

        return $submission->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Subscriber;
    }

    public function update(User $user, Submission $submission): bool
    {
        return in_array($user->role, [UserRole::Editor, UserRole::Admin], true);
    }

    public function delete(User $user, Submission $submission): bool
    {
        if (in_array($user->role, [UserRole::Editor, UserRole::Admin], true)) {
            return true;
        }

        return $submission->user_id === $user->id && $submission->canWithdraw();
    }

    public function approve(User $user, Submission $submission): bool
    {
        return in_array($user->role, [UserRole::Editor, UserRole::Admin], true);
    }

    public function reject(User $user, Submission $submission): bool
    {
        return in_array($user->role, [UserRole::Editor, UserRole::Admin], true);
    }
}
