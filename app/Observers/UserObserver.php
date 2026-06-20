<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AdminService;

class UserObserver
{
    public function saving(User $user): void
    {
        if ($user->role === UserRole::Admin) {
            AdminService::ensureCanBecomeAdmin($user);
        }

        if ($user->role !== UserRole::Author) {
            $user->requires_approval = false;
        }
    }
}
