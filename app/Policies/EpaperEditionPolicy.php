<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\EpaperEdition;
use App\Models\User;

class EpaperEditionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canAccessAdmin();
    }

    public function view(User $user, EpaperEdition $epaperEdition): bool
    {
        return $this->canManage($user, $epaperEdition);
    }

    public function create(User $user): bool
    {
        return $user->role->canAccessAdmin();
    }

    public function update(User $user, EpaperEdition $epaperEdition): bool
    {
        return $this->canManage($user, $epaperEdition);
    }

    public function delete(User $user, EpaperEdition $epaperEdition): bool
    {
        return $this->canManage($user, $epaperEdition);
    }

    protected function canManage(User $user, EpaperEdition $epaperEdition): bool
    {
        if (in_array($user->role, [UserRole::Editor, UserRole::Admin], true)) {
            return true;
        }

        return $user->role === UserRole::Author && $epaperEdition->author_id === $user->id;
    }
}
