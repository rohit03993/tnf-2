<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->unsignedInteger('likes_count')->default(0)->after('readers_count');
        });

        Schema::table('article_reads', function (Blueprint $table) {
            $table->timestamp('liked_at')->nullable()->after('last_read_at');
        });
    }

    public function down(): void
    {
        Schema::table('article_reads', function (Blueprint $table) {
            $table->dropColumn('liked_at');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('likes_count');
        });
    }
};
