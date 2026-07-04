<?php

namespace App\Services;

use App\Models\EpaperEdition;

class EpaperPromoService
{
    /** @return array{title: string, slug: string, url: string, coverUrl: string|null, thumbUrl: string, pdfUrl: string|null}|null */
    public static function latest(): ?array
    {
        $edition = EpaperEdition::query()
            ->published()
            ->with('featuredMedia')
            ->latest('published_at')
            ->first();

        if (! $edition) {
            return null;
        }

        return static::promoFor($edition);
    }

    /** @return array{title: string, slug: string, url: string, coverUrl: string|null, thumbUrl: string, pdfUrl: string|null} */
    public static function promoFor(EpaperEdition $edition): array
    {
        $edition->loadMissing('featuredMedia');
        $coverUrl = $edition->coverImageUrl();

        return [
            'title' => $edition->title,
            'slug' => $edition->slug,
            'url' => route('epaper.show', $edition->slug),
            'coverUrl' => $coverUrl,
            'thumbUrl' => $coverUrl ?? route('og.epaper.page', $edition),
            'pdfUrl' => $coverUrl ? null : $edition->pdfPublicUrl(),
        ];
    }
}
