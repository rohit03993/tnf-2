<?php

namespace Tests\Unit;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\User;
use App\Services\UserContentTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserContentTransferServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfers_articles_before_user_delete(): void
    {
        $from = User::factory()->create(['role' => UserRole::Author]);
        $to = User::factory()->create(['role' => UserRole::Editor]);

        Article::query()->create([
            'title' => 'Transfer me',
            'slug' => 'transfer-me',
            'content' => 'Body',
            'author_id' => $from->id,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        UserContentTransferService::delete($from, $to->id);

        $this->assertDatabaseMissing('users', ['id' => $from->id]);
        $this->assertDatabaseHas('articles', [
            'slug' => 'transfer-me',
            'author_id' => $to->id,
        ]);
    }

    public function test_requires_transfer_target_when_user_has_content(): void
    {
        $user = User::factory()->create(['role' => UserRole::Author]);

        Article::query()->create([
            'title' => 'Needs transfer',
            'slug' => 'needs-transfer',
            'content' => 'Body',
            'author_id' => $user->id,
            'status' => ContentStatus::Draft,
        ]);

        $this->expectException(ValidationException::class);

        UserContentTransferService::delete($user, null);
    }

    public function test_deletes_user_without_content_without_transfer_target(): void
    {
        $user = User::factory()->create(['role' => UserRole::Subscriber]);

        UserContentTransferService::delete($user, null);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
