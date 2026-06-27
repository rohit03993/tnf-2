<x-site.layout title="Search" :seo="$seo">
    <div class="tnf-page-content">
        <x-site.page-header
            title="Search"
            description="Find news, videos, and ePaper editions"
            class="mb-6"
        />

        <form action="{{ route('search') }}" method="get" class="tnf-search-form mb-8">
            <div class="tnf-search-input-wrap">
                <svg class="tnf-search-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="search"
                    name="q"
                    value="{{ $query }}"
                    placeholder="Search news, videos, ePaper…"
                    class="tnf-search-input"
                    minlength="2"
                    required
                    autofocus
                >
            </div>
            <button type="submit" class="tnf-btn-primary shrink-0">Search</button>
        </form>

        @if(strlen($query) < 2)
            <div class="tnf-empty-state">
                <svg class="tnf-empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <p class="tnf-empty-state-title">Start searching</p>
                <p class="tnf-empty-state-desc">Enter at least 2 characters to search.</p>
            </div>
        @elseif($articles->isEmpty() && $videos->isEmpty() && $epapers->isEmpty())
            <div class="tnf-empty-state">
                <p class="tnf-empty-state-title">No results</p>
                <p class="tnf-empty-state-desc">No results for &ldquo;{{ $query }}&rdquo;.</p>
            </div>
        @else
            @if($articles->count() > 0)
                <section class="mb-10">
                    <h2 class="tnf-section-title mb-4">News ({{ $articles->total() }})</h2>
                    <div class="tnf-archive-grid">
                        @foreach($articles as $article)
                            <x-cards.news-card :article="$article" />
                        @endforeach
                    </div>
                    <div class="mt-8">{{ $articles->links() }}</div>
                </section>
            @endif

            @if($videos->isNotEmpty())
                <section class="mb-10">
                    <h2 class="tnf-section-title mb-4">Videos</h2>
                    <div class="tnf-archive-grid tnf-archive-grid--videos">
                        @foreach($videos as $video)
                            <x-cards.video-card :video="$video" />
                        @endforeach
                    </div>
                </section>
            @endif

            @if($epapers->isNotEmpty())
                <section>
                    <h2 class="tnf-section-title mb-4">ePaper</h2>
                    <div class="tnf-archive-grid tnf-archive-grid--epaper">
                        @foreach($epapers as $edition)
                            <x-cards.epaper-card :edition="$edition" />
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </div>
</x-site.layout>
