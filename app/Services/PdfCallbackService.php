<?php

namespace App\Services;

use App\Enums\PdfStatus;
use App\Jobs\GenerateOgImageJob;
use App\Models\EpaperEdition;
use App\Models\Media;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfCallbackService
{
    /** @param array<string, mixed> $payload */
    public function handle(array $payload): EpaperEdition
    {
        $edition = $this->resolveEdition($payload);

        $status = strtolower((string) ($payload['status'] ?? 'ready'));

        if (in_array($status, ['failed', 'error'], true)) {
            $edition->update([
                'pdf_status' => PdfStatus::Failed,
                'pdf_error' => (string) ($payload['error'] ?? $payload['message'] ?? 'PDF processing failed.'),
                'pdf_job_id' => $payload['job_id'] ?? $edition->pdf_job_id,
            ]);

            return $edition->fresh();
        }

        $pages = $this->normalizePages($payload);

        $edition->update([
            'pdf_status' => PdfStatus::Ready,
            'pdf_error' => null,
            'pdf_job_id' => $payload['job_id'] ?? $edition->pdf_job_id,
            'pages_json' => [
                'pages' => $pages,
                'page_count' => count($pages),
            ],
        ]);

        $edition = $edition->fresh();
        $this->syncFeaturedFromFirstPage($edition, $pages);
        $edition = $edition->fresh();
        GenerateOgImageJob::dispatchSync('epaper', $edition->id);

        return $edition->fresh();
    }

    /** @param array<string, mixed> $payload */
    protected function resolveEdition(array $payload): EpaperEdition
    {
        $externalId = (string) ($payload['external_id'] ?? '');

        if (preg_match('/edition-(\d+)/', $externalId, $matches)) {
            return EpaperEdition::query()->findOrFail((int) $matches[1]);
        }

        if (isset($payload['edition_id'])) {
            return EpaperEdition::query()->findOrFail((int) $payload['edition_id']);
        }

        abort(422, 'Could not resolve ePaper edition from callback payload.');
    }

    /** @param array<string, mixed> $payload @return list<array{page: int, url: string, width: int|null, height: int|null}> */
    protected function normalizePages(array $payload): array
    {
        $raw = $payload['pages'] ?? $payload['pages_json']['pages'] ?? $payload['pages_json'] ?? [];

        if (! is_array($raw)) {
            return [];
        }

        return collect($raw)
            ->values()
            ->map(function ($item, int $index) {
                if (is_string($item)) {
                    return [
                        'page' => $index + 1,
                        'url' => $item,
                        'width' => null,
                        'height' => null,
                    ];
                }

                return [
                    'page' => (int) ($item['page'] ?? $index + 1),
                    'url' => (string) ($item['url'] ?? $item['image_url'] ?? ''),
                    'width' => isset($item['width']) ? (int) $item['width'] : null,
                    'height' => isset($item['height']) ? (int) $item['height'] : null,
                ];
            })
            ->filter(fn (array $page) => filled($page['url']))
            ->values()
            ->all();
    }

    /** @param list<array{page: int, url: string, width: int|null, height: int|null}> $pages */
    protected function syncFeaturedFromFirstPage(EpaperEdition $edition, array $pages): void
    {
        $firstPageUrl = $pages[0]['url'] ?? null;

        if (! $firstPageUrl) {
            return;
        }

        $storageMarker = '/storage/';

        if (str_contains($firstPageUrl, $storageMarker)) {
            $path = ltrim(Str::after($firstPageUrl, $storageMarker), '/');

            if (Storage::disk('public')->exists($path)) {
                $media = Media::query()->updateOrCreate(
                    ['path' => $path],
                    [
                        'disk' => 'public',
                        'mime' => Storage::disk('public')->mimeType($path),
                        'size' => Storage::disk('public')->size($path),
                        'alt' => $edition->title,
                    ],
                );

                $edition->update(['featured_media_id' => $media->id]);

                return;
            }
        }

        try {
            $response = Http::timeout(20)->get($firstPageUrl);

            if (! $response->successful()) {
                return;
            }

            $path = "epaper/renders/{$edition->id}/page-1.jpg";
            Storage::disk('public')->put($path, $response->body());

            $media = Media::query()->updateOrCreate(
                ['path' => $path],
                [
                    'disk' => 'public',
                    'mime' => 'image/jpeg',
                    'size' => Storage::disk('public')->size($path),
                    'alt' => $edition->title,
                ],
            );

            $edition->update(['featured_media_id' => $media->id]);
        } catch (\Throwable) {
            // Featured sync is best-effort; pages_json still powers the viewer.
        }
    }
}
