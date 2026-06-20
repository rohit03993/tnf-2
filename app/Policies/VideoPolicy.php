<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Video;

class VideoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canAccessAdmin();
    }

    public function view(User $user, Video $video): bool
    {
        return $this->canManage($user, $video);
    }

    public function create(User $user): bool
    {
        return $user->role->canAccessAdmin();
    }

    public function update(User $user, Video $video): bool
    {
        return $this->canManage($user, $video);
    }

    public function delete(User $user, Video $video): bool
    {
        if ($user->role === UserRole::Author) {
            return false;
        }

        return $this->canManage($user, $video);
    }

    protected function canManage(User $user, Video $video): bool
    {
        if (in_array($user->role, [UserRole::Editor, UserRole::Admin], true)) {
            return true;
        }

        return $user->role === UserRole::Author && $video->author_id === $user->id;
    }
}
