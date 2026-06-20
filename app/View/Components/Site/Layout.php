<?php

namespace App\View\Components\Site;

use App\Services\SiteChromeService;
use App\Support\SeoMeta;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Layout extends Component
{
    public SeoMeta $seo;

    public function __construct(
        public bool $authLite = false,
        public ?string $title = null,
        public bool $epaperViewer = false,
        public bool $compactChrome = false,
        ?SeoMeta $seo = null,
        ?string $description = null,
        ?string $canonical = null,
        ?string $image = null,
    ) {
        $this->seo = $seo ?? new SeoMeta(
            title: $title ?? config('app.name', 'TNF Today'),
            description: $description,
            url: $canonical,
            image: $image,
        );
    }

    public function render(): View|Closure|string
    {
        return view('components.site.layout', [
            'chrome' => SiteChromeService::chrome($this->authLite),
            'pageTitle' => $this->seo->pageTitle(),
            'seo' => $this->seo,
        ]);
    }
}
