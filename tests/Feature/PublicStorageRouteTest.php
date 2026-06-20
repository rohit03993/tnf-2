<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicStorageRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_storage_route_serves_public_disk_files(): void
    {
        $directory = storage_path('app/public/epaper/covers');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($directory.'/test.jpg', 'jpeg-bytes');

        $response = $this->get('/storage/epaper/covers/test.jpg');

        $response->assertOk();

        @unlink($directory.'/test.jpg');
    }
}
