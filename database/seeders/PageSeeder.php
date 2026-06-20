<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => '<p>TNF Today is your trusted source for news, videos, and ePaper editions.</p>',
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact-us',
                'content' => '<p>Reach us at contact@tnftoday.com</p>',
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => '<p>Your privacy matters to us.</p>',
            ],
            [
                'title' => 'Terms of Use',
                'slug' => 'terms-of-use',
                'content' => '<p>Please read these terms carefully before using TNF Today.</p>',
            ],
        ];

        foreach ($pages as $page) {
            Page::query()->updateOrCreate(
                ['slug' => $page['slug']],
                $page,
            );
        }
    }
}
