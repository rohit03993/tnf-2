<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayStoreSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_legal_pages_command_updates_privacy_and_terms(): void
    {
        $this->artisan('tnf:sync-legal-pages')
            ->assertExitCode(0);

        $privacy = Page::query()->where('slug', 'privacy-policy')->first();
        $terms = Page::query()->where('slug', 'terms-of-use')->first();

        $this->assertNotNull($privacy);
        $this->assertNotNull($terms);
        $this->assertStringContainsString('Delete Account', $privacy->content);
        $this->assertStringContainsString('Terms of Use', $terms->title);
    }

    public function test_create_play_reviewer_command_creates_subscriber_account(): void
    {
        $this->artisan('tnf:create-play-reviewer', [
            '--email' => 'reviewer@tnftoday.com',
            '--password' => 'ReviewPass123!',
        ])->assertExitCode(0);

        $user = User::query()->where('email', 'reviewer@tnftoday.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::Subscriber, $user->role);
        $this->assertTrue($user->is_active);
    }

    public function test_account_page_links_to_profile_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account'))
            ->assertOk()
            ->assertSee('Profile &amp; delete account', false);
    }

    public function test_profile_page_uses_site_layout_and_delete_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Delete Account')
            ->assertSee('Back to My Account');
    }
}
