<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canAccessAdmin();
    }

    public function view(User $user, Article $article): bool
    {
        return $this->canManage($user, $article);
    }

    public function create(User $user): bool
    {
        return $user->role->canAccessAdmin();
    }

    public function update(User $user, Article $article): bool
    {
        return $this->canManage($user, $article);
    }

    public function delete(User $user, Article $article): bool
    {
        if ($user->role === UserRole::Author) {
            return false;
        }

        return $this->canManage($user, $article);
    }

    public function publish(User $user, Article $article): bool
    {
        if (in_array($user->role, [UserRole::Editor, UserRole::Admin], true)) {
            return true;
        }

        return $user->canSelfPublishArticles() && $article->author_id === $user->id;
    }

    protected function canManage(User $user, Article $article): bool
    {
        if (in_array($user->role, [UserRole::Editor, UserRole::Admin], true)) {
            return true;
        }

        return $user->role === UserRole::Author && $article->author_id === $user->id;
    }
}
