<?php

namespace Tests\Feature;

use App\Mail\ContactInquiry;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('contact_email', 'contact@tnftoday.com');
        Setting::set('contact_phone', '+19412359817');
        Setting::set('contact_company', 'TNF Today Media Network Pvt Ltd');
    }

    public function test_contact_page_shows_email_phone_and_form(): void
    {
        $this->get(route('page.contact'))
            ->assertOk()
            ->assertSee('Contact Us')
            ->assertSee('contact@tnftoday.com')
            ->assertSee('+19412359817')
            ->assertSee('TNF Today Media Network Pvt Ltd')
            ->assertSee('Send us a message');
    }

    public function test_contact_form_submission_sends_mail_and_redirects_with_success(): void
    {
        Mail::fake();

        $response = $this->post(route('page.contact.submit'), [
            'name' => 'Test Reader',
            'email' => 'reader@example.com',
            'phone' => '+91 98765 43210',
            'subject' => 'General enquiry',
            'message' => 'Hello, I would like more information about TNF Today.',
        ]);

        $response
            ->assertRedirect(route('page.contact'))
            ->assertSessionHas('success');

        Mail::assertSent(ContactInquiry::class, function (ContactInquiry $mail) {
            return $mail->inquiry['email'] === 'reader@example.com'
                && $mail->inquiry['name'] === 'Test Reader';
        });
    }

    public function test_site_layout_includes_favicon_links(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('favicon.svg', false)
            ->assertSee('apple-touch-icon.svg', false)
            ->assertSee('site.webmanifest', false);
    }
}
