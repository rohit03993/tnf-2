@php
    /** @var \App\Support\SeoMeta $seo */
@endphp

@if($seo->description)
    <meta name="description" content="{{ $seo->description }}">
@endif

@if($seo->noindex)
    <meta name="robots" content="noindex, follow">
@endif

@if($seo->url)
    <link rel="canonical" href="{{ $seo->url }}">
@endif

<meta property="og:site_name" content="{{ config('app.name', 'TNF Today') }}">
<meta property="og:title" content="{{ $seo->title }}">
@if($seo->description)
    <meta property="og:description" content="{{ $seo->description }}">
@endif
@if($seo->url)
    <meta property="og:url" content="{{ $seo->url }}">
@endif
<meta property="og:type" content="{{ $seo->type }}">
@if($seo->image)
    <meta property="og:image" content="{{ $seo->image }}">
@endif

<meta name="twitter:card" content="{{ $seo->image ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo->title }}">
@if($seo->description)
    <meta name="twitter:description" content="{{ $seo->description }}">
@endif
@if($seo->image)
    <meta name="twitter:image" content="{{ $seo->image }}">
@endif

@if($seo->jsonLd)
    <script type="application/ld+json">{!! json_encode($seo->jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
