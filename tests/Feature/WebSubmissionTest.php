<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Submission;
use App\Models\User;
use App\Services\SubmissionWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_submit_from_my_account_form(): void
    {
        $member = User::factory()->create(['role' => UserRole::Subscriber]);
        $category = Category::query()->create(['name' => 'National', 'slug' => 'national']);

        $response = $this->actingAs($member)->post(route('account.submissions.store'), [
            'title' => 'Member headline',
            'content' => str_repeat('Story body paragraph. ', 12),
            'category_id' => $category->id,
        ]);

        $response->assertRedirect(route('account'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('submissions', [
            'user_id' => $member->id,
            'title' => 'Member headline',
            'category_id' => $category->id,
            'status' => SubmissionStatus::Pending->value,
        ]);
    }

    public function test_editor_approval_creates_published_article(): void
    {
        $member = User::factory()->create(['role' => UserRole::Subscriber]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $category = Category::query()->create(['name' => 'Sports', 'slug' => 'sports']);

        $submission = Submission::query()->create([
            'user_id' => $member->id,
            'title' => 'Approved story',
            'content' => '<p>Full story content here.</p>',
            'category_id' => $category->id,
            'status' => SubmissionStatus::Pending,
        ]);

        $article = SubmissionWorkflowService::approve($submission, $editor, [$category->id]);

        $this->assertSame(ContentStatus::Published, $article->status);
        $this->assertSame('Approved story', $article->title);
        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'status' => SubmissionStatus::Approved->value,
            'promoted_article_id' => $article->id,
        ]);
        $this->assertTrue($article->categories->contains('id', $category->id));
    }

    public function test_my_account_page_loads_for_member(): void
    {
        $member = User::factory()->create(['role' => UserRole::Subscriber]);

        $response = $this->actingAs($member)->get(route('account'));

        $response->assertOk();
        $response->assertSee('My Account', false);
    }
}
