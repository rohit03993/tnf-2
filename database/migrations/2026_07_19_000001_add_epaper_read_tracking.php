<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('epaper_editions', function (Blueprint $table) {
            $table->unsignedInteger('readers_count')->default(0)->after('published_at');
            $table->unsignedInteger('views_count')->default(0)->after('readers_count');
        });

        Schema::create('epaper_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epaper_edition_id')->constrained('epaper_editions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reader_key', 64);
            $table->dateTime('first_read_at');
            $table->dateTime('last_read_at');
            $table->timestamps();

            $table->unique(['epaper_edition_id', 'reader_key']);
            $table->index(['epaper_edition_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epaper_reads');

        Schema::table('epaper_editions', function (Blueprint $table) {
            $table->dropColumn(['readers_count', 'views_count']);
        });
    }
};
