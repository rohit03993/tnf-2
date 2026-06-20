<x-site.layout title="TNF Today — News, Videos & ePaper" :seo="$seo">
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
                <div class="tnf-hero-section-headlines rounded-tnf-lg bg-white p-4 shadow-card">
                    <h2 class="tnf-section-title mb-3">Latest Headlines</h2>
                    @forelse($heroHeadlines as $index => $article)
                        <x-cards.headline-item :article="$article" :number="$index + 1" />
                    @empty
                        <p class="text-tnf-sm text-tnf-muted">No headlines yet.</p>
                    @endforelse
                </div>
            </section>

            {{-- Sidebar: trending + top news (right on desktop, after hero on mobile) --}}
            <aside class="tnf-home-sidebar space-y-6">
                @if($settings['show_trending'] && $trendingNews->isNotEmpty())
                    <div class="rounded-tnf-lg bg-white p-4 shadow-card">
                        <h2 class="tnf-section-title mb-3">Trending News</h2>
                        @foreach($trendingNews as $article)
                            <x-cards.trending-item :article="$article" />
                        @endforeach
                    </div>
                @endif

                @if($sidebarTopNews->isNotEmpty())
                    <div class="rounded-tnf-lg bg-white p-4 shadow-card">
                        <h2 class="tnf-section-title mb-3">Top News</h2>
                        @foreach($sidebarTopNews as $index => $article)
                            <x-cards.headline-item :article="$article" :number="$index + 1" />
                        @endforeach
                    </div>
                @endif
            </aside>

            {{-- Main feed --}}
            <div class="tnf-home-feed space-y-6 lg:space-y-8">
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
                <section class="tnf-epaper-teaser rounded-tnf-lg bg-gradient-to-r from-tnf-navy to-tnf-navy-light p-6 text-white shadow-card">
                    <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <h2 class="text-tnf-xl font-bold">Today's ePaper</h2>
                            <p class="mt-1 text-tnf-sm text-white/80">
                                @if($latestEpaper)
                                    {{ $latestEpaper->title }}
                                @else
                                    Read the latest digital edition
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('epaper.index') }}" class="tnf-btn-primary shrink-0">
                            Read ePaper
                        </a>
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
</x-site.layout>
