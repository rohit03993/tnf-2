<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        // MySQL / MariaDB: allow accounts with phone only (no email).
        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        DB::table('users')->whereNull('email')->orderBy('id')->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                DB::table('users')->where('id', $user->id)->update([
                    'email' => 'user_'.$user->id.'@phone.tnftoday.local',
                ]);
            }
        });

        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');
    }
};
