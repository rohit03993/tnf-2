<?php

namespace App\Support;

class SeoMeta
{
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $image = null,
        public ?string $url = null,
        public string $type = 'website',
        public ?array $jsonLd = null,
        public bool $noindex = false,
        public ?int $imageWidth = null,
        public ?int $imageHeight = null,
        public ?string $imageAlt = null,
    ) {}

    public function pageTitle(): string
    {
        $site = config('app.name', 'TNF Today');

        if ($this->title === $site) {
            return $site;
        }

        return $this->title.' — '.$site;
    }
}
