@props(['chrome'])

<header class="tnf-header">
    <div class="tnf-container tnf-header-top">
        <x-site.logo :logo="$chrome['siteLogo'] ?? null" />

        <nav class="tnf-header-nav" aria-label="Primary">
            @foreach($chrome['primaryNav'] as $item)
                @php
                    $isActive = match ($item['slug'] ?? '') {
                        'videos' => request()->routeIs('videos.*'),
                        default => request()->url() === $item['url'],
                    };
                @endphp
                @if(($item['slug'] ?? '') === 'more')
                    <div class="tnf-more-menu group">
                        <button type="button" class="tnf-header-nav-link" aria-haspopup="true">
                            {{ $item['label'] }} ▾
                        </button>
                        <div class="tnf-more-dropdown" role="menu">
                            @foreach($chrome['drawerGroups'] as $groupTitle => $links)
                                <p class="tnf-drawer-group-title">{{ $groupTitle }}</p>
                                @foreach($links as $link)
                                    <a href="{{ $link['url'] }}" class="tnf-drawer-link" role="menuitem">
                                        {{ $link['label'] }}
                                    </a>
                                @endforeach
                            @endforeach
                            <p class="tnf-drawer-group-title">More</p>
                            <a href="{{ route('videos.index') }}" class="tnf-drawer-link" role="menuitem">Videos</a>
                            <a href="{{ route('epaper.index') }}" class="tnf-drawer-link" role="menuitem">ePaper</a>
                            @auth
                                <a href="{{ route('account') }}" class="tnf-drawer-link" role="menuitem">My Account</a>
                            @else
                                <a href="{{ route('login') }}" class="tnf-drawer-link" role="menuitem">Sign In</a>
                                @if(config('tnf.allow_public_registration'))
                                    <a href="{{ route('register') }}" class="tnf-drawer-link" role="menuitem">Register</a>
                                @endif
                            @endauth
                        </div>
                    </div>
                @else
                    <a href="{{ $item['url'] }}"
                       class="tnf-header-nav-link {{ $isActive ? 'tnf-header-nav-link--active' : '' }}">
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="tnf-header-actions">
            <a href="{{ route('search') }}" class="tnf-header-icon-btn hidden lg:inline-flex" aria-label="Search">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </a>
            <a href="{{ route('epaper.index') }}" class="tnf-header-btn-epaper hidden mobile-md:inline-flex" aria-label="e-Paper">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="hidden lg:inline">e-Paper</span>
            </a>
            @auth
                @if(auth()->user()->role->canAccessAdmin() && !($isApp ?? false))
                    <a href="{{ url('/admin') }}" class="tnf-header-btn-signin hidden mobile-md:inline-flex">Admin</a>
                @else
                    <a href="{{ route('account') }}" class="tnf-header-btn-signin hidden mobile-md:inline-flex">My Account</a>
                @endif
            @else
                <a href="{{ route('login') }}" class="tnf-header-btn-signin hidden mobile-md:inline-flex">Sign In</a>
            @endauth
            <button type="button"
                    class="tnf-header-icon-btn lg:hidden"
                    data-tnf-drawer-toggle
                    aria-label="Open menu"
                    aria-expanded="false"
                    aria-controls="tnf-drawer">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>
</header>

@if(filled($chrome['whatsappUrl']))
    <x-site.whatsapp-bar :url="$chrome['whatsappUrl']" />
@endif
