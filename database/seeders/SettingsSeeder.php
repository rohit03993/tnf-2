<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'breaking_count' => 12,
            'top_stories_count' => 6,
            'featured_videos_count' => 4,
            'recent_news_count' => 9,
            'trending_count' => 8,
            'show_featured_videos' => true,
            'show_trending' => true,
            'show_crime' => true,
            'banner_image' => '',
            'banner_link_url' => '',
            'disclaimer_text' => 'TNF Today provides news and information for general awareness. Verify important facts independently.',
            'disclaimer_email' => 'contact@tnftoday.com',
            'credits_line' => 'Designed & Developed with Love by Pal Digital',
            'whatsapp_url' => '',
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
