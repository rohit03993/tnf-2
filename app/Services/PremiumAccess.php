<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\EpaperEdition;
use App\Models\User;

class PremiumAccess
{
    public static function userHasPremium(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($user->role === UserRole::Editor) {
            return true;
        }

        if ($user->role === UserRole::Author) {
            return true;
        }

        if ($user->subscription_active) {
            return true;
        }

        return false;
    }

    public static function canViewRestrictedEpaper(?User $user, EpaperEdition $edition): bool
    {
        if (! $edition->restricted) {
            return true;
        }

        return static::userHasPremium($user);
    }
}
