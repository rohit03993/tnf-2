<x-site.layout title="Design Preview — TNF Today">
    <div class="tnf-page-content space-y-8">
        <p class="text-tnf-sm text-tnf-muted">Phase D/E component preview</p>

        <section class="grid gap-4 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <article class="tnf-hero-lead">
                    <div class="tnf-skeleton aspect-[16/9] w-full"></div>
                    <div class="tnf-news-card-body">
                        <h2 class="tnf-news-card-title tnf-line-clamp-2 font-devanagari">मुख्य खबर — हीरो स्टोरी</h2>
                        <p class="tnf-news-card-meta">May 26, 2026</p>
                    </div>
                </article>
            </div>
            <div class="rounded-tnf-lg bg-white p-4 shadow-card">
                <h3 class="tnf-section-title mb-3">Latest Headlines</h3>
                @for ($i = 1; $i <= 5; $i++)
                    <div class="tnf-headline-list-item">
                        <span class="tnf-headline-list-num">{{ $i }}</span>
                        <div class="tnf-skeleton tnf-headline-list-thumb"></div>
                        <span class="tnf-headline-list-title tnf-line-clamp-2 font-devanagari">हेडलाइन {{ $i }}</span>
                    </div>
                @endfor
            </div>
        </section>

        <section>
            <div class="tnf-cat-block-header">
                <h3 class="tnf-section-title">Featured Videos</h3>
                <a href="{{ route('videos.index') }}" class="text-tnf-sm font-semibold text-tnf-red">See all videos</a>
            </div>
            <div class="tnf-scroll-rail">
                @for ($i = 1; $i <= 5; $i++)
                    <article class="tnf-video-card">
                        <div class="tnf-skeleton aspect-[9/16] w-full"></div>
                        <span class="tnf-video-card-badge">Short</span>
                        <span class="tnf-video-card-play">▶</span>
                        <p class="tnf-video-card-title tnf-line-clamp-2 font-devanagari">वीडियो {{ $i }}</p>
                    </article>
                @endfor
            </div>
        </section>

        <x-site.share-bar title="Sample article" url="{{ url('/') }}" />
    </div>
</x-site.layout>
