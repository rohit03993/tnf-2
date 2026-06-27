<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0F1320">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    @php
        $tnfBuildStamp = is_file(public_path('build/manifest.json'))
            ? (string) filemtime(public_path('build/manifest.json'))
            : 'none';
    @endphp
    <!-- tnf-ui-build:{{ $tnfBuildStamp }} -->

    <title>{{ $pageTitle }}</title>

    <x-site.seo-meta :seo="$seo" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;500;600;700&family=Noto+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet"
        media="print"
        onload="this.media='all'"
    >
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;500;600;700&family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    </noscript>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(request()->routeIs('home'))
        @vite(['resources/js/home.js'])
    @endif
    @if($isApp ?? false)
        @vite(['resources/js/mobile-bridge.js'])
    @endif
    @stack('head')
    @stack('styles')
</head>
<body
    @class([
        'tnf-auth-lite' => $chrome['authLite'],
        'tnf-no-bottom-nav' => $chrome['authLite'] || ($epaperViewer ?? false),
        'tnf-epaper-viewer-page' => $epaperViewer ?? false,
        'tnf-app-mode' => $isApp ?? false,
    ])
    x-data="tnfSite()"
    @if($isApp ?? false) data-tnf-app="1" @endif
    @if(request()->routeIs('home')) data-tnf-home="1" @endif
>
    @if(request()->routeIs('article.show', 'videos.show'))
        <div class="tnf-reading-progress" id="tnf-reading-progress" aria-hidden="true"></div>
    @endif

    @unless($chrome['authLite'])
        <x-site.header :chrome="$chrome" />
        @unless($compactChrome ?? false)
            <x-site.masthead-banner :image="$chrome['bannerImage']" :url="$chrome['bannerLink']" />
        @endunless
        <x-site.topic-pills :tags="$chrome['hotTags'] ?? collect()" />
        <x-site.drawer :groups="$chrome['drawerGroups']" />
    @endunless

    <main id="tnf-main">
        {{ $slot }}
    </main>

    @unless($chrome['authLite'])
        <x-site.footer
            :disclaimer="$chrome['disclaimerText']"
            :email="$chrome['disclaimerEmail']"
            :credits="$chrome['creditsLine']"
        />

        <button type="button" onclick="window.scrollTo({top:0,behavior:'smooth'})"
            class="tnf-back-to-top" aria-label="Back to top">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-width="2" d="M5 15l7-7 7 7"/>
            </svg>
        </button>

        @if($isApp ?? false)
            <x-site.app-bottom-nav />
        @else
            <x-site.web-bottom-nav />
        @endif
    @endunless

    @if($isApp ?? false)
        <x-site.app-page-loader />
        <x-site.app-offline-overlay />
    @endif

    @stack('scripts')
</body>
</html>
