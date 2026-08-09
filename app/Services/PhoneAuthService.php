<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PhoneAuthService
{
    public function findOrCreateByVerifiedPhone(string $phone, ?string $name = null): User
    {
        $phone = PhoneNumber::normalize($phone);

        /** @var User|null $user */
        $user = User::query()->where('phone', $phone)->first();

        if ($user) {
            $updates = [
                'phone_verified_at' => $user->phone_verified_at ?? now(),
                'whatsapp_opt_in' => true,
                'whatsapp_opt_in_at' => $user->whatsapp_opt_in_at ?? now(),
                'is_active' => true,
            ];

            if (filled($name) && (blank($user->name) || str_starts_with((string) $user->name, 'User '))) {
                $updates['name'] = trim($name);
            }

            $user->forceFill($updates)->save();

            return $user;
        }

        $displayName = filled($name) ? trim($name) : ('User '.substr((string) $phone, -4));

        return User::query()->create([
            'name' => $displayName,
            'email' => null,
            'phone' => $phone,
            'phone_verified_at' => now(),
            'whatsapp_opt_in' => true,
            'whatsapp_opt_in_at' => now(),
            'password' => Hash::make(Str::password(32)),
            'role' => UserRole::Subscriber,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
