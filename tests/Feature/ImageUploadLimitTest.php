<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImageUploadLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_rejects_image_over_150_kb(): void
    {
        config(['tnf.max_image_kb' => 150]);

        $member = User::factory()->create(['role' => UserRole::Subscriber]);
        $category = Category::query()->create(['name' => 'National', 'slug' => 'national']);

        $response = $this->actingAs($member)->post(route('account.submissions.store'), [
            'title' => 'Oversized image story',
            'content' => str_repeat('Story body paragraph. ', 12),
            'category_id' => $category->id,
            'image' => UploadedFile::fake()->image('large.jpg')->size(151),
        ]);

        $response->assertSessionHasErrors('image');
    }

    public function test_submission_accepts_image_at_150_kb(): void
    {
        config(['tnf.max_image_kb' => 150]);

        $member = User::factory()->create(['role' => UserRole::Subscriber]);
        $category = Category::query()->create(['name' => 'National', 'slug' => 'national']);

        $response = $this->actingAs($member)->post(route('account.submissions.store'), [
            'title' => 'Sized image story',
            'content' => str_repeat('Story body paragraph. ', 12),
            'category_id' => $category->id,
            'image' => UploadedFile::fake()->image('ok.jpg')->size(150),
        ]);

        $response->assertRedirect(route('account'));
        $response->assertSessionHasNoErrors();
    }
}
