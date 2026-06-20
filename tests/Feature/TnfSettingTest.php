<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\TnfSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TnfSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_setting_overrides_env_fallback(): void
    {
        Setting::set('pdf_service_url', 'https://pdf.example.test');

        $this->assertSame('https://pdf.example.test', TnfSetting::get('pdf_service_url'));
    }
}
