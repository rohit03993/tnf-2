<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\PdfStatus;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Category;
use App\Models\EpaperEdition;
use App\Models\Media;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoContentSeeder extends Seeder
{
    /** @var array<string, list<string>> */
    protected array $headlines = [
        'national' => [
            'देशभर में मानसून की पहली बारिश, कई राज्यों में राहत',
            'केंद्र सरकार ने नई शिक्षा नीति पर राज्यों के साथ बैठक बुलाई',
            'राष्ट्रीय राजमार्ग प्राधिकरण ने 12 नए कॉरिडोर की योजना जारी की',
            'भारत ने अंतरिक्ष मिशन में नया मील का पत्थर हासिल किया',
        ],
        'health' => [
            'आयुष मंत्रालय ने नई स्वास्थ्य योजना की घोषणा की',
            'विशेषज्ञों ने गर्मियों में हीट स्ट्रोक से बचाव के टिप्स दिए',
            'सरकारी अस्पतालों में 24x7 आपातकालीन सेवाएं शुरू',
            'डायबिटीज रोकथाम पर राष्ट्रीय अभियान की शुरुआत',
        ],
        'religion' => [
            'काशी में गंगा आरती में रिकॉर्ड संख्या में श्रद्धालु शामिल',
            'प्रमुख तीर्थ स्थलों पर सुविधाओं के आधुनिकीकरण की योजना',
            'धार्मिक नेताओं ने सामाजिक सद्भाव पर संयुक्त बयान जारी किया',
            'मंदिर प्रबंध समिति ने नए दर्शन समय की घोषणा की',
        ],
        'politics' => [
            'संसद सत्र में महत्वपूर्ण विधेयक पर गहन चर्चा',
            'राज्य चुनाव की तैयारियों में सभी दलों ने तेजी दिखाई',
            'मुख्यमंत्री ने जनसमस्याओं पर समीक्षा बैठक की अध्यक्षता की',
            'विपक्ष ने सरकार से जवाब मांगते हुए नोटिस दिया',
        ],
        'sports' => [
            'भारतीय क्रिकेट टीम ने टी20 सीरीज में शानदार जीत दर्ज की',
            'ओलंपिक तैयारी के लिए नए खेल अकादमी केंद्र खुले',
            'प्रो कबड्डी लीग में घरेलू टीम ने फाइनल में जगह बनाई',
            'राष्ट्रीय फुटबॉल चैंपियनशिप का उद्घाटन आज',
        ],
        'business' => [
            'शेयर बाजार ने सप्ताह की शुरुआत में नई ऊंचाई छुई',
            'स्टार्टअप इकोसिस्टम में निवेश रिकॉर्ड स्तर पर',
            'RBI ने ब्याज दरों पर महत्वपूर्ण फैसला सुरक्षित रखा',
            'MSME क्षेत्र के लिए नई सब्सिडी योजना लागू',
        ],
        'entertainment' => [
            'बॉलीवुड की नई फिल्म ने रिलीज के पहले दिन धमाल मचाया',
            'टीवी सीरियल के कलाकारों ने फैंस मीट का आयोजन किया',
            'संगीत समारोह में देशभर के कलाकारों ने हिस्सा लिया',
            'OTT प्लेटफॉर्म ने क्षेत्रीय सामग्री पर नया ध्यान दिया',
        ],
        'tech' => [
            'भारतीय AI स्टार्टअप ने वैश्विक पुरस्कार जीता',
            '5G कवरेज अब 500 से अधिक शहरों में उपलब्ध',
            'साइबर सुरक्षा पर केंद्र ने नई दिशानिर्देश जारी किए',
            'इलेक्ट्रिक वाहन बिक्री में पिछले महीने 40% वृद्धि',
        ],
        'exclusive' => [
            'TNF Exclusive: बड़े नीति बदलाव की तैयारी में सरकार',
            'विशेष रिपोर्ट: ग्रामीण भारत में रोजगार के नए अवसर',
            'खास साक्षात्कार: उद्योग जगत के दिग्गज ने भविष्य पर बात की',
            'TNF स्पेशल: चुनावी मैदान की अंदर की कहानी',
        ],
        'lifestyle' => [
            'गर्मियों में त्वचा की देखभाल के आसान उपाय',
            'युवा डिजाइनरों का फैशन शो दिल्ली में धूमदार',
            'घरेलू यात्रा गंतव्य जहां बजट में मिलेगा आराम',
            'फिटनेस विशेषज्ञों ने सुबह की दिनचर्या पर सुझाव दिए',
        ],
        'cultural' => [
            'वाराणसी में सांस्कृतिक महोत्सव का भव्य आयोजन',
            'लोक कला को बढ़ावा देने के लिए नया संग्रहालय खुला',
            'शास्त्रीय संगीत की शाम में युवा कलाकारों ने मोह लिया',
            'पारंपरिक हस्तशिल्प मेले में कारीगरों की भीड़ उमड़ी',
        ],
        'crime' => [
            'पुलिस ने साइबर फ्रॉड गिरोह का भंडाफोड किया',
            'बैंक धोखाधड़ी मामले में तीन आरोपियों को गिरफ्तार किया',
            'ट्रैफिक नियमों के कड़े क्रियान्वयन से सड़क दुर्घटनाएं घटीं',
            'एंटी-नारकोटिक्स सेल ने बड़ी मात्रा में बरामदगी की',
        ],
    ];

    public function run(): void
    {
        $author = User::query()
            ->where('role', UserRole::Admin)
            ->first() ?? User::query()->first();

        if (! $author) {
            $this->command?->error('No user found. Create an admin first: php artisan tnf:assign-admin your@email.com');

            return;
        }

        $categories = Category::query()->get()->keyBy('slug');

        if ($categories->isEmpty()) {
            $this->command?->error('No categories found. Run: php artisan db:seed --class=CategorySeeder');

            return;
        }

        if (Article::query()->where('slug', 'not like', 'demo-%')->exists()) {
            $this->command?->info('Skipping demo news — imported WordPress articles are present.');
        } else {
            $this->command?->info('Seeding demo news articles with images…');

            $articleIndex = 0;

            foreach ($this->headlines as $categorySlug => $titles) {
                $category = $categories->get($categorySlug);

                if (! $category) {
                    continue;
                }

                foreach ($titles as $index => $title) {
                    $articleIndex++;
                    $slug = 'demo-'.$categorySlug.'-'.($index + 1);
                    $publishedAt = now()->subHours($articleIndex * 3);

                    $media = $this->storeImage(
                        seed: "tnf-{$categorySlug}-{$index}",
                        filename: "news/{$slug}.jpg",
                        width: 800,
                        height: 600,
                        alt: $title,
                    );

                    $article = Article::query()->updateOrCreate(
                        ['slug' => $slug],
                        [
                            'title' => $title,
                            'content' => $this->body($title),
                            'excerpt' => Str::limit(strip_tags($this->body($title)), 160),
                            'author_id' => $author->id,
                            'status' => ContentStatus::Published,
                            'featured_media_id' => $media?->id,
                            'comment_count' => $this->commentCount($articleIndex),
                            'published_at' => $publishedAt,
                        ],
                    );

                    $article->categories()->sync([$category->id]);
                }
            }
        }

        $this->command?->info('Seeding demo videos…');

        $demoVideos = [
            ['title' => 'ब्रेकिंग: देश की प्रमुख खबरें', 'youtube' => 'https://www.youtube.com/watch?v=9Auq9mYxFEE'],
            ['title' => 'खेल जगत की ताज़ा अपडेट', 'youtube' => 'https://www.youtube.com/watch?v=ScMzIvxBSi4'],
            ['title' => 'राजनीति पर विशेष चर्चा', 'youtube' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ'],
            ['title' => 'स्वास्थ्य और जीवनशैली टिप्स', 'youtube' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ'],
            ['title' => 'मनोरंजन की दुनिया', 'youtube' => 'https://www.youtube.com/watch?v=ScMzIvxBSi4'],
            ['title' => 'तकनीक और नवाचार', 'youtube' => 'https://www.youtube.com/watch?v=9Auq9mYxFEE'],
        ];

        foreach ($demoVideos as $index => $videoData) {
            $slug = 'demo-video-'.($index + 1);
            $media = $this->storeImage(
                seed: "tnf-video-{$index}",
                filename: "videos/{$slug}.jpg",
                width: 400,
                height: 711,
                alt: $videoData['title'],
            );

            Video::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $videoData['title'],
                    'content' => '<p>Demo video for TNF Today video archive and single pages.</p>',
                    'excerpt' => 'Featured video from TNF Today.',
                    'author_id' => $author->id,
                    'embed_url' => $videoData['youtube'],
                    'featured_media_id' => $media?->id,
                    'status' => ContentStatus::Published,
                    'published_at' => now()->subHours(($index + 1) * 5),
                ],
            );
        }

        $this->command?->info('Seeding demo ePaper editions…');

        $epaperEditions = [
            ['title' => 'TNF Today — '.now()->format('j F Y'), 'days_ago' => 0, 'seed' => 'tnf-epaper-today'],
            ['title' => 'TNF Today — '.now()->subDay()->format('j F Y'), 'days_ago' => 1, 'seed' => 'tnf-epaper-yesterday'],
            ['title' => 'TNF Today — '.now()->subDays(2)->format('j F Y'), 'days_ago' => 2, 'seed' => 'tnf-epaper-2days'],
        ];

        foreach ($epaperEditions as $edition) {
            $slug = 'demo-epaper-'.$edition['days_ago'];
            $pages = [];
            $coverMedia = null;

            for ($page = 1; $page <= 4; $page++) {
                $pageMedia = $this->storeImage(
                    seed: "{$edition['seed']}-p{$page}",
                    filename: "epaper/{$slug}-p{$page}.jpg",
                    width: 600,
                    height: 800,
                    alt: $edition['title']." — page {$page}",
                );

                if ($page === 1) {
                    $coverMedia = $pageMedia;
                }

                if ($pageMedia?->url()) {
                    $pages[] = [
                        'page' => $page,
                        'url' => $pageMedia->url(),
                        'width' => 600,
                        'height' => 800,
                    ];
                }
            }

            EpaperEdition::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $edition['title'],
                    'content' => '<p>Digital edition of TNF Today.</p>',
                    'excerpt' => 'Read today\'s complete newspaper online.',
                    'author_id' => $author->id,
                    'pdf_path' => null,
                    'restricted' => false,
                    'pdf_status' => PdfStatus::Ready,
                    'pages_json' => [
                        'pages' => $pages,
                        'page_count' => count($pages),
                    ],
                    'featured_media_id' => $coverMedia?->id,
                    'status' => ContentStatus::Published,
                    'published_at' => now()->subDays($edition['days_ago'])->setTime(6, 0),
                ],
            );
        }

        Cache::forget('homepage.data');
        Cache::forget('site.chrome.full');
        Cache::forget('site.chrome.auth');

        $this->command?->info('Demo content ready: '
            .Article::query()->where('slug', 'like', 'demo-%')->count().' articles, '
            .Video::query()->where('slug', 'like', 'demo-%')->count().' videos, '
            .EpaperEdition::query()->where('slug', 'like', 'demo-%')->count().' ePaper editions.');
        $this->command?->warn('Ensure public storage is linked: php artisan storage:link');
    }

    protected function body(string $title): string
    {
        return <<<HTML
<p><strong>{$title}</strong> — यह डेमो समाचार लेख TNF Today होमपेज पर सेक्शन और कैटेगरी रेल्स का परीक्षण करने के लिए जोड़ा गया है।</p>
<p>विस्तृत रिपोर्ट के अनुसार, यह घटना क्षेत्र में व्यापक चर्चा का विषय बनी हुई है। स्थानीय प्रशासन ने स्थिति पर नजर रखने और आवश्यक कदम उठाने का आश्वासन दिया है।</p>
<p>विशेषज्ञों का मानना है कि आने वाले दिनों में इस मामले पर और अपडेट सामने आ सकते हैं। TNF Today आपको नवीनतम जानकारी के साथ अपडेट रखेगा।</p>
HTML;
    }

    protected function commentCount(int $index): int
    {
        return match ($index % 6) {
            0 => 120,
            1 => 95,
            2 => 78,
            3 => 64,
            4 => 52,
            default => 30 + ($index % 20),
        };
    }

    protected function storeImage(string $seed, string $filename, int $width, int $height, string $alt): ?Media
    {
        $disk = 'public';
        $path = "demo/{$filename}";

        if (! Storage::disk($disk)->exists($path)) {
            $bytes = $this->downloadImageBytes($seed, $width, $height)
                ?? $this->placeholderImageBytes($width, $height, $seed);

            if ($bytes === null) {
                return null;
            }

            Storage::disk($disk)->makeDirectory(dirname($path));
            Storage::disk($disk)->put($path, $bytes);
        }

        return Media::query()->updateOrCreate(
            ['path' => $path],
            [
                'disk' => $disk,
                'mime' => 'image/jpeg',
                'size' => Storage::disk($disk)->size($path),
                'alt' => $alt,
            ],
        );
    }

    protected function downloadImageBytes(string $seed, int $width, int $height): ?string
    {
        try {
            $response = Http::timeout(20)
                ->withHeaders(['User-Agent' => 'TNF-Today-Demo-Seeder'])
                ->get("https://picsum.photos/seed/{$seed}/{$width}/{$height}.jpg");

            return $response->successful() ? $response->body() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function placeholderImageBytes(int $width, int $height, string $label): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $image = imagecreatetruecolor($width, $height);
        $red = imagecolorallocate($image, 188, 30, 56);
        $navy = imagecolorallocate($image, 15, 19, 32);
        $white = imagecolorallocate($image, 255, 255, 255);

        imagefilledrectangle($image, 0, 0, $width, $height, $navy);
        imagefilledrectangle($image, 0, 0, $width, 12, $red);
        imagestring($image, 5, 20, (int) ($height / 2) - 10, 'TNF Today Demo', $white);
        imagestring($image, 3, 20, (int) ($height / 2) + 12, Str::limit($label, 28, ''), $white);

        ob_start();
        imagejpeg($image, null, 85);
        imagedestroy($image);

        return ob_get_clean() ?: null;
    }
}
