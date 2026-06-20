<?php

namespace App\Services;

use App\Models\EpaperEdition;
use App\Models\User;

class EpaperAccessService
{
    /** @return null|'premium' */
    public static function gate(?User $user, EpaperEdition $edition): ?string
    {
        if (self::isPublicEdition($edition, $user)) {
            return null;
        }

        if (PremiumAccess::canViewRestrictedEpaper($user, $edition)) {
            return null;
        }

        return 'premium';
    }

    public static function isPublicEdition(EpaperEdition $edition, ?User $user = null): bool
    {
        if (! $edition->restricted) {
            return true;
        }

        if (str_starts_with($edition->slug, 'demo-')) {
            return true;
        }

        if ($user && config('tnf.epaper_local_auth_access')) {
            return true;
        }

        return false;
    }
}
