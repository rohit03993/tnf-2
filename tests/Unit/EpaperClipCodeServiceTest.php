<?php

namespace Tests\Unit;

use App\Services\EpaperClipCodeService;
use Tests\TestCase;

class EpaperClipCodeServiceTest extends TestCase
{
    public function test_encode_decode_round_trip(): void
    {
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);

        $clip = [
            'page' => 5,
            'x' => 0.3822,
            'y' => 0.3895,
            'w' => 0.6059,
            'h' => 0.2437,
        ];

        $token = EpaperClipCodeService::encode(42, $clip);
        $decoded = EpaperClipCodeService::decode($token);

        $this->assertNotNull($decoded);
        $this->assertSame(42, $decoded['edition_id']);
        $this->assertSame(5, $decoded['page']);
        $this->assertEqualsWithDelta(0.3822, $decoded['x'], 0.0001);
        $this->assertEqualsWithDelta(0.3895, $decoded['y'], 0.0001);
        $this->assertEqualsWithDelta(0.6059, $decoded['w'], 0.0001);
        $this->assertEqualsWithDelta(0.2437, $decoded['h'], 0.0001);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $token);
        $this->assertLessThanOrEqual(40, strlen($token));
    }

    public function test_tampered_token_is_rejected(): void
    {
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);

        $token = EpaperClipCodeService::encode(1, [
            'page' => 1,
            'x' => 0.1,
            'y' => 0.1,
            'w' => 0.2,
            'h' => 0.2,
        ]);

        $tampered = substr($token, 0, -1).(substr($token, -1) === 'A' ? 'B' : 'A');

        $this->assertNull(EpaperClipCodeService::decode($tampered));
    }
}
