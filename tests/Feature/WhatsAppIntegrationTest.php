<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\WhatsAppCloudService;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_number_normalizes_indian_mobile(): void
    {
        $this->assertSame('919876543210', PhoneNumber::normalize('98765 43210'));
        $this->assertSame('919876543210', PhoneNumber::normalize('+91-9876543210'));
    }

    public function test_connection_status_reports_connected_number(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                ->push([
                    'display_phone_number' => '+91 98765 43210',
                    'verified_name' => 'TNF Today',
                    'quality_rating' => 'GREEN',
                ], 200)
                ->push([
                    'id' => 'waba-1',
                    'name' => 'TNF WABA',
                ], 200),
        ]);

        Setting::set('whatsapp_access_token', 'token');
        Setting::set('whatsapp_phone_number_id', 'phone-id');
        Setting::set('whatsapp_business_account_id', 'waba-1');

        $status = app(WhatsAppCloudService::class)->connectionStatus();

        $this->assertTrue($status['connected']);
        $this->assertSame('TNF Today', $status['verified_name']);
        $this->assertSame('TNF WABA', $status['business_name']);
    }

    public function test_webhook_verify_challenge(): void
    {
        Setting::set('whatsapp_webhook_verify_token', 'secret-token');

        $response = $this->get('/webhooks/whatsapp?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'secret-token',
            'hub.challenge' => '12345',
        ]));

        $response->assertOk();
        $response->assertSee('12345');
    }

    public function test_webhook_verify_accepts_underscore_keys(): void
    {
        Setting::set('whatsapp_webhook_verify_token', 'secret-token');

        $response = $this->get('/webhooks/whatsapp?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'secret-token',
            'hub_challenge' => '999',
        ]));

        $response->assertOk();
        $response->assertSee('999');
    }

    public function test_webhook_stores_inbound_message(): void
    {
        Setting::set('whatsapp_app_secret', '');
        config(['app.env' => 'local']);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'id' => 'wamid.TEST1',
                            'from' => '919876543210',
                            'timestamp' => (string) now()->timestamp,
                            'type' => 'text',
                            'text' => ['body' => 'Hello TNF'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/webhooks/whatsapp', $payload)->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'wamid' => 'wamid.TEST1',
            'direction' => 'inbound',
            'phone' => '919876543210',
            'body' => 'Hello TNF',
        ]);
    }

    public function test_local_otp_flow_logs_in_user(): void
    {
        config(['app.env' => 'local']);
        Setting::set('whatsapp_enabled', false);

        $this->post('/login/phone', ['phone' => '9876543210'])
            ->assertRedirect(route('login.phone'));

        $challenge = \App\Models\OtpChallenge::query()->latest('id')->first();
        $this->assertNotNull($challenge);

        // Recover code from hash by brute force is silly; call service verify with known code by recreating.
        $challenge->forceFill(['code_hash' => Hash::make('123456')])->save();

        $this->withSession(['phone_login_phone' => '919876543210'])
            ->post('/login/phone/verify', [
                'phone' => '919876543210',
                'otp' => '123456',
            ])
            ->assertRedirect();

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'phone' => '919876543210',
            'whatsapp_opt_in' => 1,
        ]);
    }

    public function test_sync_message_templates_caches_approved_list(): void
    {
        Http::fake([
            'graph.facebook.com/*/message_templates*' => Http::response([
                'data' => [
                    [
                        'id' => '1',
                        'name' => 'tnf_login_otp',
                        'language' => 'en',
                        'status' => 'APPROVED',
                        'category' => 'AUTHENTICATION',
                        'components' => [
                            ['type' => 'BODY', 'text' => 'Your code is {{1}}'],
                        ],
                    ],
                    [
                        'id' => '2',
                        'name' => 'tnf_news_alert',
                        'language' => 'hi',
                        'status' => 'PENDING',
                        'category' => 'MARKETING',
                        'components' => [
                            ['type' => 'BODY', 'text' => '{{1}} {{2}}'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        Setting::set('whatsapp_access_token', 'token');
        Setting::set('whatsapp_business_account_id', 'waba-1');

        $service = app(WhatsAppCloudService::class);
        $result = $service->syncMessageTemplates();

        $this->assertTrue($result['ok']);
        $this->assertSame(2, $result['count']);
        $this->assertSame(1, $result['approved']);

        $options = $service->approvedTemplatePickOptions();
        $this->assertArrayHasKey('tnf_login_otp||en', $options);
        $this->assertArrayNotHasKey('tnf_news_alert||hi', $options);
    }

    public function test_content_alert_creates_outbound_message(): void
    {
        Http::fake([
            'graph.facebook.com/*/messages' => Http::response([
                'messages' => [['id' => 'wamid.OUT1']],
            ], 200),
        ]);

        Setting::set('whatsapp_enabled', true);
        Setting::set('whatsapp_access_token', 'token');
        Setting::set('whatsapp_phone_number_id', 'phone-id');
        Setting::set('whatsapp_news_template', 'tnf_news_alert');

        User::factory()->create([
            'phone' => '919811122233',
            'whatsapp_opt_in' => true,
        ]);

        $ok = app(WhatsAppCloudService::class)->sendContentAlert(
            '919811122233',
            'Test headline',
            '/n/1',
            'news',
        );

        $this->assertTrue($ok);
        $this->assertDatabaseHas('whatsapp_messages', [
            'wamid' => 'wamid.OUT1',
            'direction' => 'outbound',
            'phone' => '919811122233',
        ]);
    }
}
