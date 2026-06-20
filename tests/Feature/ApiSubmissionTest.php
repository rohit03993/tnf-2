<?php

namespace Tests\Feature;

use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscriber_can_submit_with_category(): void
    {
        $member = User::factory()->create(['role' => UserRole::Subscriber]);
        $category = Category::query()->create(['name' => 'Health', 'slug' => 'health']);

        $response = $this->actingAs($member)->postJson('/api/v1/submissions', [
            'title' => 'Test headline',
            'content' => str_repeat('Story content. ', 10),
            'category_id' => $category->id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.category_id', $category->id);
        $response->assertJsonPath('data.category', 'Health');

        $this->assertDatabaseHas('submissions', [
            'user_id' => $member->id,
            'category_id' => $category->id,
            'status' => SubmissionStatus::Pending->value,
        ]);
    }

    public function test_submission_requires_category(): void
    {
        $member = User::factory()->create(['role' => UserRole::Subscriber]);

        $response = $this->actingAs($member)->postJson('/api/v1/submissions', [
            'title' => 'Test headline',
            'content' => str_repeat('Story content. ', 10),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['category_id']);
        $this->assertSame(0, Submission::query()->count());
    }
}
