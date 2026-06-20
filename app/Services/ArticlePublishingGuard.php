<?php

namespace App\Services;

use App\Models\User;

class ArticlePublishingGuard
{
    public static function enforce(User $user, array $data): array
    {
        return ContentPublishingGuard::enforce($user, $data);
    }
}
