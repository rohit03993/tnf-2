<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\ContentPublishingGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContentPublishingGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_reporter_cannot_publish_without_category(): void
    {
        $reporter = User::factory()->create([
            'role' => UserRole::Author,
            'requires_approval' => true,
        ]);

        $this->expectException(ValidationException::class);

        ContentPublishingGuard::enforce($reporter, [
            'status' => ContentStatus::Pending->value,
            'categories' => [],
        ]);
    }

    public function test_admin_publish_sets_published_at_when_missing(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $data = ContentPublishingGuard::enforce($admin, [
            'status' => ContentStatus::Published->value,
            'published_at' => null,
        ]);

        $this->assertSame(ContentStatus::Published->value, $data['status']);
        $this->assertNotEmpty($data['published_at']);
    }

    public function test_reporter_can_publish_with_category(): void
    {
        $reporter = User::factory()->create([
            'role' => UserRole::Author,
            'requires_approval' => false,
        ]);

        $data = ContentPublishingGuard::enforce($reporter, [
            'status' => ContentStatus::Published->value,
            'categories' => [1],
        ]);

        $this->assertSame(ContentStatus::Published->value, $data['status']);
        $this->assertNotEmpty($data['published_at']);
    }
}
