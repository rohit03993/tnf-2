<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Support\LegalPageContent;
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
            ...LegalPageContent::pages(),
        ];

        foreach ($pages as $page) {
            Page::query()->updateOrCreate(
                ['slug' => $page['slug']],
                $page,
            );
        }
    }
}
