<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->json('param_mappings')->nullable()->after('param_count');
        });

        Schema::create('whatsapp_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_template_id')->constrained('whatsapp_templates')->cascadeOnDelete();
            $table->string('name');
            $table->string('status', 20)->default('draft')->index();
            $table->string('audience_type', 40)->default('opted_in');
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->json('campaign_variables')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shot_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('shot_at')->nullable();
            $table->timestamps();
        });

        Schema::create('whatsapp_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_campaign_id')->constrained('whatsapp_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone', 32);
            $table->string('wamid')->nullable();
            $table->string('status', 20)->default('pending');
            $table->json('template_params')->nullable();
            $table->text('message_sent')->nullable();
            $table->json('provider_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['whatsapp_campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_campaign_recipients');
        Schema::dropIfExists('whatsapp_campaigns');

        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropColumn('param_mappings');
        });
    }
};
