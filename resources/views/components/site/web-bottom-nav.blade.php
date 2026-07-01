<nav class="tnf-bottom-nav lg:hidden" aria-label="Mobile navigation">
    <div class="tnf-bottom-nav-inner">
        <a href="{{ route('home') }}"
           class="tnf-bottom-nav-item {{ request()->routeIs('home') ? 'tnf-bottom-nav-item--active' : '' }}">
            <span class="tnf-bottom-nav-item-inner">
                <svg class="tnf-bottom-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="tnf-bottom-nav-label">Home</span>
            </span>
        </a>
        <a href="{{ route('videos.index') }}"
           class="tnf-bottom-nav-item {{ request()->routeIs('videos.*') ? 'tnf-bottom-nav-item--active' : '' }}">
            <span class="tnf-bottom-nav-item-inner">
                <svg class="tnf-bottom-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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
                <svg class="tnf-bottom-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <span class="tnf-bottom-nav-label">Menu</span>
            </span>
        </button>
    </div>
</nav>
