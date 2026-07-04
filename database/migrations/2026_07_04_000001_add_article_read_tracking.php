<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->unsignedInteger('readers_count')->default(0)->after('comment_count');
            $table->unsignedInteger('views_count')->default(0)->after('readers_count');
        });

        Schema::create('article_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reader_key', 64);
            $table->timestamp('first_read_at');
            $table->timestamp('last_read_at');
            $table->timestamps();

            $table->unique(['article_id', 'reader_key']);
            $table->index(['article_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_reads');

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['readers_count', 'views_count']);
        });
    }
};
