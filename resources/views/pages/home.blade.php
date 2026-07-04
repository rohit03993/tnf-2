<x-site.layout title="TNF Today — News, Videos & ePaper" :seo="$seo">
    @php
        $epaperPromo = $latestEpaper ? \App\Services\EpaperPromoService::promoFor($latestEpaper) : null;
        $epaperBg = asset('images/epaper-section-bg.jpg');
    @endphp

    <div class="tnf-page-content tnf-home">
        <div class="tnf-home-layout">
            {{-- Hero + Latest Headlines --}}
            <section class="tnf-hero-section">
                <div class="tnf-hero-section-main">
                    @if($heroLead)
                        <x-cards.news-card :article="$heroLead" :featured="true" />
                    @else
                        <div class="tnf-hero-lead rounded-tnf-lg bg-white p-8 text-center shadow-card">
                            <p class="text-tnf-muted">Publish news in admin to see the hero story.</p>
                        </div>
                    @endif
                </div>
                <div class="tnf-home-panel tnf-hero-section-headlines">
                    <div class="tnf-home-panel__head">
                        <h2 class="tnf-section-title">Latest Headlines</h2>
                    </div>
                    <div class="tnf-home-panel__body">
                        @forelse($heroHeadlines as $index => $article)
                            <x-cards.headline-item :article="$article" :number="$index + 1" />
                        @empty
                            <p class="px-2 py-3 text-tnf-sm text-tnf-muted">No headlines yet.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            {{-- Sidebar: trending + top news (right on desktop, after hero on mobile) --}}
            <aside class="tnf-home-sidebar space-y-4 lg:space-y-5">
                @if($settings['show_trending'] && $trendingNews->isNotEmpty())
                    <div class="tnf-home-panel">
                        <div class="tnf-home-panel__head">
                            <h2 class="tnf-section-title">Trending News</h2>
                        </div>
                        <div class="tnf-home-panel__body">
                            @foreach($trendingNews as $article)
                                <x-cards.trending-item :article="$article" />
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($sidebarTopNews->isNotEmpty())
                    <div class="tnf-home-panel tnf-home-top-news">
                        <div class="tnf-home-panel__head">
                            <h2 class="tnf-section-title">Top News</h2>
                        </div>
                        <div class="tnf-home-panel__body">
                            @foreach($sidebarTopNews as $index => $article)
                                <x-cards.headline-item :article="$article" :number="$index + 1" />
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>

            {{-- Main feed --}}
            <div class="tnf-home-feed">
                {{-- Featured Videos --}}
                @if($settings['show_featured_videos'] && $featuredVideos->isNotEmpty())
                    <section class="tnf-featured-videos tnf-cat-block">
                        <div class="tnf-cat-block-header">
                            <h2 class="tnf-section-title">Featured Videos</h2>
                            <a href="{{ route('videos.index') }}" class="tnf-see-more-btn">
                                See More
                            </a>
                        </div>
                        <div class="tnf-scroll-rail tnf-featured-videos-grid">
                            @foreach($featuredVideos as $video)
                                <x-cards.video-card :video="$video" />
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- ePaper teaser --}}
                <section class="tnf-epaper-teaser">
                    <div class="tnf-epaper-teaser-bg" style="background-image: url('{{ $epaperBg }}');" aria-hidden="true"></div>
                    <div class="tnf-epaper-teaser-overlay" aria-hidden="true"></div>
                    <div class="tnf-epaper-teaser-inner">
                        @if($epaperPromo)
                            <a href="{{ $epaperPromo['url'] }}" class="tnf-epaper-teaser-cover-link" aria-label="Open {{ $epaperPromo['title'] }}">
                                <x-site.epaper-thumb :promo="$epaperPromo" variant="teaser" />
                            </a>
                        @endif
                        <div class="tnf-epaper-teaser-copy">
                            <p class="tnf-epaper-teaser-kicker">Digital newspaper</p>
                            <h2 class="tnf-epaper-teaser-title">Today's ePaper</h2>
                            <p class="tnf-epaper-teaser-desc">
                                @if($latestEpaper)
                                    {{ $latestEpaper->title }}
                                @else
                                    Read the latest digital edition from TNF Today.
                                @endif
                            </p>
                            <a href="{{ $epaperPromo['url'] ?? route('epaper.index') }}" class="tnf-epaper-teaser-btn">
                                Read ePaper
                            </a>
                        </div>
                    </div>
                </section>

                {{-- Category rails --}}
                @foreach($categoryRails as $rail)
                    <x-site.category-rail :category="$rail['category']" :articles="$rail['articles']" />
                @endforeach

                {{-- Recent news --}}
                @if($recentNews->isNotEmpty())
                    <section class="tnf-recent-news tnf-cat-block">
                        <div class="tnf-cat-block-header">
                            <h2 class="tnf-section-title">Recent News</h2>
                            <a href="{{ route('search') }}" class="tnf-see-more-btn">
                                See More
                            </a>
                        </div>
                        <div class="tnf-cat-block-grid">
                            @foreach($recentNews as $article)
                                <x-cards.news-card :article="$article" />
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </div>

    @if($epaperPromo && ! $epaperPromo['coverUrl'] && $epaperPromo['pdfUrl'])
        @push('scripts')
            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
            @vite(['resources/js/epaper-covers.js'])
        @endpush
    @endif
</x-site.layout>
