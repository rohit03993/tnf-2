<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->string('media_id')->nullable()->after('type');
            $table->string('media_path')->nullable()->after('media_id');
            $table->string('media_mime_type')->nullable()->after('media_path');
            $table->string('media_filename')->nullable()->after('media_mime_type');
            $table->text('caption')->nullable()->after('media_filename');
        });

        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('language', 20)->default('en');
            $table->string('status', 40)->default('PENDING')->index();
            $table->string('category', 40)->nullable();
            $table->unsignedTinyInteger('param_count')->default(0);
            $table->text('body')->nullable();
            $table->json('components')->nullable();
            $table->json('provider_meta')->nullable();
            $table->string('meta_template_id')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['name', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn([
                'media_id',
                'media_path',
                'media_mime_type',
                'media_filename',
                'caption',
            ]);
        });
    }
};
