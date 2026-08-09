<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->boolean('whatsapp_opt_in')->default(false)->after('phone_verified_at');
            $table->timestamp('whatsapp_opt_in_at')->nullable()->after('whatsapp_opt_in');
        });

        Schema::create('otp_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->string('code_hash');
            $table->string('purpose', 40)->default('login');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('wamid')->nullable()->unique();
            $table->string('direction', 16); // inbound | outbound
            $table->string('phone', 20)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40)->default('text');
            $table->text('body')->nullable();
            $table->json('payload')->nullable();
            $table->string('status', 40)->nullable();
            $table->string('template_name')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('provider_timestamp')->nullable();
            $table->timestamps();

            $table->index(['phone', 'created_at']);
            $table->index(['direction', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('otp_challenges');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'phone_verified_at', 'whatsapp_opt_in', 'whatsapp_opt_in_at']);
        });
    }
};
