<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AdminService
{
    public static function hasAdmin(): bool
    {
        return User::query()->where('role', UserRole::Admin)->exists();
    }

    public static function currentAdmin(): ?User
    {
        return User::query()->where('role', UserRole::Admin)->first();
    }

    /**
     * Assign the sole administrator. Demotes any existing admin first.
     * Use this command for initial setup or admin transfer only.
     */
    public static function assignAdmin(User $user): void
    {
        User::withoutEvents(function () use ($user) {
            User::query()
                ->where('role', UserRole::Admin)
                ->where('id', '!=', $user->id)
                ->update(['role' => UserRole::Subscriber]);

            $user->update([
                'role' => UserRole::Admin,
                'subscription_active' => true,
            ]);
        });
    }

    public static function ensureCanBecomeAdmin(User $user): void
    {
        $existingAdmin = User::query()
            ->where('role', UserRole::Admin)
            ->when($user->exists, fn ($query) => $query->where('id', '!=', $user->id))
            ->exists();

        if ($existingAdmin) {
            throw ValidationException::withMessages([
                'role' => 'Only one administrator is allowed. An admin account already exists.',
            ]);
        }
    }
}
