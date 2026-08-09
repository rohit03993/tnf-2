<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PhoneAuthService
{
    public function findOrCreateByVerifiedPhone(string $phone): User
    {
        $phone = PhoneNumber::normalize($phone);

        /** @var User|null $user */
        $user = User::query()->where('phone', $phone)->first();

        if ($user) {
            $user->forceFill([
                'phone_verified_at' => $user->phone_verified_at ?? now(),
                'whatsapp_opt_in' => true,
                'whatsapp_opt_in_at' => $user->whatsapp_opt_in_at ?? now(),
                'is_active' => true,
            ])->save();

            return $user;
        }

        return User::query()->create([
            'name' => 'User '.substr($phone, -4),
            'email' => 'wa_'.$phone.'@phone.tnftoday.local',
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
