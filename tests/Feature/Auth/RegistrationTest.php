<?php

namespace Tests\Feature\Auth;

use App\Models\OtpChallenge;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Mobile number');
        $response->assertSee('No email required');
    }

    public function test_new_users_can_register_with_phone_otp(): void
    {
        Setting::set('whatsapp_enabled', true);
        Setting::set('whatsapp_access_token', 'token');
        Setting::set('whatsapp_phone_number_id', 'phone-id');
        Setting::set('whatsapp_otp_template', 'login_otp');

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]], 200),
        ]);

        $this->post('/register', [
            'name' => 'Test User',
            'phone' => '9876543210',
        ])->assertRedirect(route('register'));

        $challenge = OtpChallenge::query()->latest('id')->first();
        $this->assertNotNull($challenge);

        // Recover code by checking known hash in local test: recreate by verifying with extracted approach
        // Instead, plant a known OTP challenge.
        OtpChallenge::query()->delete();
        OtpChallenge::query()->create([
            'phone' => '919876543210',
            'code_hash' => Hash::make('123456'),
            'purpose' => 'login',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->withSession([
            'phone_login_phone' => '919876543210',
            'phone_login_name' => 'Test User',
        ])->post('/register/verify', [
            'phone' => '919876543210',
            'otp' => '123456',
            'name' => 'Test User',
        ])->assertRedirect();

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'phone' => '919876543210',
            'name' => 'Test User',
        ]);

        $user = User::query()->where('phone', '919876543210')->first();
        $this->assertNull($user?->email);
    }
}
