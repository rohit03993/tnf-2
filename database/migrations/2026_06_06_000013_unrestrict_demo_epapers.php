<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('epaper_editions')
            ->where('slug', 'like', 'demo-%')
            ->update(['restricted' => false]);
    }

    public function down(): void
    {
        DB::table('epaper_editions')
            ->where('slug', 'demo-epaper-0')
            ->update(['restricted' => true]);
    }
};
