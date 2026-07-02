<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreatePlayReviewerCommand extends Command
{
    protected $signature = 'tnf:create-play-reviewer
                            {--email=reviewer@tnftoday.com : Reviewer login email}
                            {--password= : Reviewer password (generated if omitted)}
                            {--name=Play Store Reviewer : Display name}';

    protected $description = 'Create or reset the Google Play Store reviewer account (subscriber role)';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->option('email')));
        $password = (string) ($this->option('password') ?: Str::password(16));
        $name = trim((string) $this->option('name'));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid --email is required.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user && $user->role->canAccessAdmin()) {
            $this->error('This email belongs to an admin/staff account. Use a dedicated reviewer email.');

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => UserRole::Subscriber,
                'is_active' => true,
                'requires_approval' => false,
                'subscription_active' => false,
                'email_verified_at' => now(),
            ],
        );

        $this->info($user->wasRecentlyCreated ? 'Play Store reviewer account created.' : 'Play Store reviewer account updated.');
        $this->newLine();
        $this->line('Email: '.$email);
        $this->line('Password: '.$password);
        $this->line('Role: '.$user->role->label());
        $this->newLine();
        $this->line('Play Console → App access → provide these credentials.');
        $this->line('Reviewer flow: Account tab → Sign In → browse Home, ePaper, Videos → Profile & delete account.');

        return self::SUCCESS;
    }
}
