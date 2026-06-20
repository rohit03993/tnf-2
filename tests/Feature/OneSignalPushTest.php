<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\OneSignalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OneSignalPushTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_is_skipped_when_disabled(): void
    {
        Http::fake();

        Setting::set('onesignal_app_id', 'test-app-id');
        Setting::set('onesignal_rest_key', 'test-rest-key');
        Setting::set('push_enabled', false);
        Setting::set('frontend_url', 'https://tnftoday.com');

        $service = app(OneSignalService::class);

        $this->assertFalse($service->isEnabled());
        $this->assertFalse($service->send('Title', 'Body', '/news/test'));

        Http::assertNothingSent();
    }

    public function test_push_sends_absolute_url_when_enabled(): void
    {
        Http::fake([
            'onesignal.com/*' => Http::response(['id' => 'notification-1'], 200),
        ]);

        Setting::set('onesignal_app_id', 'test-app-id');
        Setting::set('onesignal_rest_key', 'test-rest-key');
        Setting::set('push_enabled', true);
        Setting::set('frontend_url', 'https://tnftoday.com');

        $sent = app(OneSignalService::class)->send(
            'Breaking',
            'Story excerpt',
            '/demo-article',
        );

        $this->assertTrue($sent);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://onesignal.com/api/v1/notifications'
                && ($body['url'] ?? '') === 'https://tnftoday.com/demo-article';
        });
    }
}
