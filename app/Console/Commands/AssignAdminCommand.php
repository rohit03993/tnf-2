<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AdminService;
use Illuminate\Console\Command;

class AssignAdminCommand extends Command
{
    protected $signature = 'tnf:assign-admin {email : The email of the user to make admin}';

    protected $description = 'Assign the sole TNF administrator (only one admin allowed)';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No user found with that email. Create the user first via /admin or register.');

            return self::FAILURE;
        }

        $previousAdmin = AdminService::currentAdmin();

        AdminService::assignAdmin($user);

        if ($previousAdmin && $previousAdmin->id !== $user->id) {
            $this->warn("Previous admin demoted: {$previousAdmin->email}");
        }

        $this->info("Administrator assigned: {$user->email}");

        return self::SUCCESS;
    }
}
