<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ExportNewsSeederCommand extends Command
{
    protected $signature = 'tnf:export-news-seeder
                            {--output=database/seeders/ImportedNewsSeeder.php : Output seeder file path}';

    protected $description = 'Export imported articles into a Laravel seeder (text only, no images)';

    public function handle(): int
    {
        $articles = Article::query()
            ->with(['categories:id,slug', 'tags:id,slug'])
            ->orderBy('published_at')
            ->get();

        if ($articles->isEmpty()) {
            $this->error('No articles in the database. Import WordPress news first.');

            return self::FAILURE;
        }

        $output = $this->option('output');
        $dir = dirname($output);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $rows = $articles->map(fn (Article $article) => $this->articleRow($article))->all();
        $json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $php = $this->buildSeederFile($json, count($rows));

        file_put_contents($output, $php);

        $this->info("Seeder written: {$output}");
        $this->line('Add ImportedNewsSeeder::class to DatabaseSeeder when you want demo data from real news.');

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    protected function articleRow(Article $article): array
    {
        return [
            'title' => $article->title,
            'slug' => $article->slug,
            'content' => $article->content,
            'excerpt' => $article->excerpt,
            'status' => $article->status->value,
            'embed_url' => $article->embed_url,
            'comment_count' => $article->comment_count,
            'published_at' => $article->published_at?->toDateTimeString(),
            'categories' => $article->categories->pluck('slug')->all(),
            'tags' => $article->tags->pluck('slug')->all(),
        ];
    }

    protected function buildSeederFile(string $json, int $count): string
    {
        return <<<PHP
<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Generated from imported WordPress news — text only, no images.
 * Regenerate: php artisan tnf:export-news-seeder
 */
class ImportedNewsSeeder extends Seeder
{
    public function run(): void
    {
        \$authorId = AdminService::currentAdmin()?->id
            ?? User::query()->orderBy('id')->value('id');

        if (! \$authorId) {
            \$this->command?->warn('No users found — skipping imported news seeder.');

            return;
        }

        \$articles = json_decode(<<<'JSON'
{$json}
JSON, true);

        foreach (\$articles as \$row) {
            \$article = Article::query()->updateOrCreate(
                ['slug' => \$row['slug']],
                [
                    'title' => \$row['title'],
                    'content' => \$row['content'],
                    'excerpt' => \$row['excerpt'] ?? null,
                    'author_id' => \$authorId,
                    'status' => ContentStatus::from(\$row['status']),
                    'embed_url' => \$row['embed_url'] ?? null,
                    'comment_count' => (int) (\$row['comment_count'] ?? 0),
                    'published_at' => \$row['published_at'] ?? null,
                ],
            );

            \$categoryIds = collect(\$row['categories'] ?? [])
                ->map(fn (string \$slug) => Category::query()->firstOrCreate(
                    ['slug' => \$slug],
                    ['name' => Str::headline(\$slug)],
                )->id)
                ->all();

            \$tagIds = collect(\$row['tags'] ?? [])
                ->map(fn (string \$slug) => Tag::query()->firstOrCreate(
                    ['slug' => \$slug],
                    ['name' => Str::headline(\$slug)],
                )->id)
                ->all();

            \$article->categories()->sync(\$categoryIds);
            \$article->tags()->sync(\$tagIds);
        }

        \$this->command?->info('Imported news seeder: {$count} articles.');
    }
}

PHP;
    }
}
