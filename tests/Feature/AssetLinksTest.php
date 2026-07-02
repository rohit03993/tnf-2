<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_assetlinks_returns_play_console_json(): void
    {
        $this->get('/.well-known/assetlinks.json')
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('0.target.package_name', 'com.tnftoday.news')
            ->assertJsonPath('0.relation.0', 'delegate_permission/common.handle_all_urls')
            ->assertJsonPath('0.relation.1', 'delegate_permission/common.get_login_creds')
            ->assertJsonPath('0.target.sha256_cert_fingerprints.0', '62:B2:1F:24:8D:62:7D:BA:73:E9:7D:FB:8C:A9:27:17:3A:24:61:5D:B7:07:1B:DE:29:B9:A1:42:83:CA:48:2A');
    }
}
