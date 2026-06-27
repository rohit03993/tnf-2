@props(['groups'])

@php
    $isActive = fn (string $url): bool => url()->current() === $url;

    $primary = [
        ['label' => 'Home', 'url' => route('home'), 'active' => request()->routeIs('home'), 'icon' => 'home'],
        ['label' => 'ePaper', 'url' => route('epaper.index'), 'active' => request()->routeIs('epaper.*'), 'icon' => 'epaper'],
        ['label' => 'Videos', 'url' => route('videos.index'), 'active' => request()->routeIs('videos.*'), 'icon' => 'video'],
        ['label' => 'Search', 'url' => route('search'), 'active' => request()->routeIs('search'), 'icon' => 'search'],
    ];

    $skipLabels = ['Home'];
    $skipUrls = collect($primary)->pluck('url')->all();

    $categories = collect($groups)
        ->flatMap(fn ($links) => $links)
        ->reject(fn ($link) => in_array($link['label'] ?? '', $skipLabels, true))
        ->reject(fn ($link) => in_array($link['url'] ?? '', $skipUrls, true))
        ->unique('url')
        ->values();
@endphp

<div class="tnf-drawer-overlay" data-tnf-drawer-close aria-hidden="true"></div>

<aside id="tnf-drawer" class="tnf-drawer" role="dialog" aria-label="Site menu" aria-modal="true">
    <div class="tnf-drawer-top">
        <div class="tnf-drawer-top-bar">
            <x-site.logo size="sm" data-tnf-drawer-close />
            <button type="button" class="tnf-drawer-close-btn" data-tnf-drawer-close aria-label="Close menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="tnf-drawer-top-accent" aria-hidden="true"></div>
    </div>

    <div class="tnf-drawer-body">
        <ul class="tnf-drawer-primary">
            @foreach($primary as $item)
                <li>
                    <a href="{{ $item['url'] }}"
                       class="tnf-drawer-primary-link {{ $item['active'] ? 'tnf-drawer-primary-link--active' : '' }}"
                       data-tnf-drawer-close>
                        <span class="tnf-drawer-primary-icon" aria-hidden="true">
                            @if($item['icon'] === 'home')
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                            @elseif($item['icon'] === 'epaper')
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            @elseif($item['icon'] === 'video')
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            @else
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            @endif
                        </span>
                        <span class="tnf-drawer-primary-label">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        @if($categories->isNotEmpty())
            <p class="tnf-drawer-label">Categories</p>
            <ul class="tnf-drawer-chips">
                @foreach($categories as $link)
                    <li>
                        <a href="{{ $link['url'] }}"
                           class="tnf-drawer-chip {{ $isActive($link['url']) ? 'tnf-drawer-chip--active' : '' }}"
                           data-tnf-drawer-close>
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="tnf-drawer-foot">
        @auth
            <a href="{{ route('account') }}" class="tnf-drawer-signin" data-tnf-drawer-close>My Account</a>
        @else
            <a href="{{ route('login') }}" class="tnf-drawer-signin" data-tnf-drawer-close>Sign In</a>
        @endauth
    </div>
</aside>
