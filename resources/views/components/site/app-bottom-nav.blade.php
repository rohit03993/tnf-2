@props(['latestEpaper' => null])

@php
    $accountUrl = auth()->check() ? route('account') : route('login');
    $accountLabel = auth()->check() ? 'Account' : 'Sign In';
    $accountActive = auth()->check()
        ? request()->routeIs('account', 'account.*')
        : request()->routeIs('login', 'register');
    $epaperUrl = ($latestEpaper['url'] ?? null) ?: route('epaper.index');
@endphp

<nav class="tnf-app-tab-bar" aria-label="App navigation">
    <div class="tnf-bottom-nav-inner">
        <a href="{{ route('home') }}" class="tnf-bottom-nav-item {{ request()->routeIs('home') ? 'tnf-bottom-nav-item--active' : '' }}">
            <span class="tnf-bottom-nav-item-inner">
                <x-site.bottom-nav-icon name="home" />
                <span class="tnf-bottom-nav-label">Home</span>
            </span>
        </a>
        <a href="{{ $epaperUrl }}" class="tnf-bottom-nav-item {{ request()->routeIs('epaper.*') ? 'tnf-bottom-nav-item--active' : '' }}">
            <span class="tnf-bottom-nav-item-inner">
                @if($latestEpaper)
                    <x-site.epaper-thumb :promo="$latestEpaper" variant="nav" />
                @else
                    <x-site.bottom-nav-icon name="epaper" />
                @endif
                <span class="tnf-bottom-nav-label">ePaper</span>
            </span>
        </a>
        <a href="{{ route('videos.index') }}" class="tnf-bottom-nav-item {{ request()->routeIs('videos.*') ? 'tnf-bottom-nav-item--active' : '' }}">
            <span class="tnf-bottom-nav-item-inner">
                <x-site.bottom-nav-icon name="videos" />
                <span class="tnf-bottom-nav-label">Videos</span>
            </span>
        </a>
        <a href="{{ $accountUrl }}" class="tnf-bottom-nav-item {{ $accountActive ? 'tnf-bottom-nav-item--active' : '' }}">
            <span class="tnf-bottom-nav-item-inner">
                <x-site.bottom-nav-icon name="account" />
                <span class="tnf-bottom-nav-label">{{ $accountLabel }}</span>
            </span>
        </a>
        <button type="button"
                class="tnf-bottom-nav-item"
                data-tnf-drawer-toggle
                aria-label="Open menu"
                aria-expanded="false"
                aria-controls="tnf-drawer">
            <span class="tnf-bottom-nav-item-inner">
                <x-site.bottom-nav-icon name="menu" />
                <span class="tnf-bottom-nav-label">Menu</span>
            </span>
        </button>
    </div>
</nav>
