<nav class="tnf-bottom-nav lg:hidden" aria-label="Mobile navigation">
    <div class="tnf-bottom-nav-inner">
        <a href="{{ route('home') }}"
           class="tnf-bottom-nav-item {{ request()->routeIs('home') ? 'tnf-bottom-nav-item--active' : '' }}">
            <span class="tnf-bottom-nav-item-inner">
                <svg class="tnf-bottom-nav-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                <span class="tnf-bottom-nav-label">Home</span>
            </span>
        </a>
        <a href="{{ route('videos.index') }}"
           class="tnf-bottom-nav-item {{ request()->routeIs('videos.*') ? 'tnf-bottom-nav-item--active' : '' }}">
            <span class="tnf-bottom-nav-item-inner">
                <svg class="tnf-bottom-nav-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                <span class="tnf-bottom-nav-label">Videos</span>
            </span>
        </a>
        <a href="{{ route('epaper.index') }}"
           class="tnf-bottom-nav-item {{ request()->routeIs('epaper.*') ? 'tnf-bottom-nav-item--active' : '' }}">
            <span class="tnf-bottom-nav-item-inner">
                <svg class="tnf-bottom-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="tnf-bottom-nav-label">ePaper</span>
            </span>
        </a>
        <button type="button"
                class="tnf-bottom-nav-item"
                data-tnf-drawer-toggle
                aria-label="Open menu"
                aria-expanded="false"
                aria-controls="tnf-drawer">
            <span class="tnf-bottom-nav-item-inner">
                <svg class="tnf-bottom-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <span class="tnf-bottom-nav-label">Menu</span>
            </span>
        </button>
    </div>
</nav>
