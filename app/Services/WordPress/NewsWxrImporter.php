<?php

namespace App\Services\WordPress;

use App\Enums\ContentStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Support\Str;

class NewsWxrImporter
{
    public const POST_TYPES = ['post', 'tnf_news'];

    public function __construct(
        protected WxrParser $parser,
    ) {}

    /**
     * @return array{imported: int, updated: int, skipped: int, errors: list<string>, categories: int}
     */
    public function import(string $path, bool $dryRun = false, bool $updateExisting = true, ?int $authorId = null): array
    {
        $authorId = $this->resolveAuthorId($authorId);
        $categoryCount = $this->importChannelCategories($path, $dryRun);

        $stats = [
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'categories' => $categoryCount,
        ];

        foreach ($this->parser->items($path) as $item) {
            try {
                $result = $this->importItem($item, $authorId, $dryRun, $updateExisting);
                $stats[$result]++;
            } catch (\Throwable $exception) {
                $stats['errors'][] = ($item['title'] ?: 'Untitled').': '.$exception->getMessage();
            }
        }

        return $stats;
    }

    protected function importChannelCategories(string $path, bool $dryRun): int
    {
        $count = 0;

        foreach ($this->parser->parseChannelCategories($path) as $category) {
            $count++;

            if ($dryRun) {
                continue;
            }

            Category::query()->firstOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name']],
            );
        }

        return $count;
    }

    /** @param array<string, mixed> $item */
    protected function importItem(array $item, int $authorId, bool $dryRun, bool $updateExisting): string
    {
        $slug = $this->resolveSlug($item);

        if ($slug === '') {
            throw new \RuntimeException('Missing slug.');
        }

        $existing = Article::query()->where('slug', $slug)->first();

        if ($existing && ! $updateExisting) {
            return 'skipped';
        }

        if ($dryRun) {
            return $existing ? 'updated' : 'imported';
        }

        $payload = [
            'title' => $item['title'] ?: 'Untitled',
            'slug' => $slug,
            'content' => $item['content'] ?: '<p></p>',
            'excerpt' => $item['excerpt'] ?: null,
            'author_id' => $authorId,
            'status' => $this->mapStatus((string) $item['status']),
            'embed_url' => $item['embed_url'],
            'comment_count' => (int) ($item['comment_count'] ?? 0),
            'published_at' => $this->mapStatus((string) $item['status']) === ContentStatus::Published
                ? ($item['published_at'] ?? now())
                : null,
        ];

        if ($existing) {
            $existing->update($payload);
            $article = $existing;
            $result = 'updated';
        } else {
            $article = Article::query()->create($payload);
            $result = 'imported';
        }

        $article->categories()->sync($this->resolveCategoryIds($item['categories'] ?? []));
        $article->tags()->sync($this->resolveTagIds($item['tags'] ?? []));

        return $result;
    }

    /** @param array<string, mixed> $item */
    protected function resolveSlug(array $item): string
    {
        $slug = Str::slug((string) ($item['slug'] ?? ''));

        if ($slug !== '') {
            return $slug;
        }

        return Str::slug((string) ($item['title'] ?? ''));
    }

    protected function mapStatus(string $wpStatus): ContentStatus
    {
        return match ($wpStatus) {
            'publish' => ContentStatus::Published,
            'pending' => ContentStatus::Pending,
            default => ContentStatus::Draft,
        };
    }

    protected function resolveAuthorId(?int $authorId): int
    {
        if ($authorId && User::query()->whereKey($authorId)->exists()) {
            return $authorId;
        }

        $admin = AdminService::currentAdmin();

        if ($admin) {
            return $admin->id;
        }

        $fallback = User::query()->orderBy('id')->value('id');

        if (! $fallback) {
            throw new \RuntimeException('No users found. Create an admin user before importing.');
        }

        return (int) $fallback;
    }

    /** @param list<array{slug: string, name: string}> $categories @return list<int> */
    protected function resolveCategoryIds(array $categories): array
    {
        return collect($categories)
            ->map(function (array $category): int {
                $slug = Str::slug($category['slug']);

                return Category::query()->firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $category['name'] ?: $slug],
                )->id;
            })
            ->unique()
            ->values()
            ->all();
    }

    /** @param list<array{slug: string, name: string}> $tags @return list<int> */
    protected function resolveTagIds(array $tags): array
    {
        return collect($tags)
            ->map(function (array $tag): int {
                $slug = Str::slug($tag['slug']);

                return Tag::query()->firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $tag['name'] ?: $slug],
                )->id;
            })
            ->unique()
            ->values()
            ->all();
    }
}
