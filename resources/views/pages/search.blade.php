<x-site.layout title="Search" :seo="$seo">
    <div class="tnf-page-content">
        <h1 class="tnf-section-title mb-6">Search</h1>

        <form action="{{ route('search') }}" method="get" class="mb-8 flex gap-2">
            <input
                type="search"
                name="q"
                value="{{ $query }}"
                placeholder="Search news, videos, and ePaper…"
                class="min-h-touch flex-1 rounded-tnf border border-tnf-gray-dark px-4 text-tnf-base focus:border-tnf-red focus:outline-none focus:ring-1 focus:ring-tnf-red"
                minlength="2"
                required
            >
            <button type="submit" class="tnf-btn-primary shrink-0">Search</button>
        </form>

        @if(strlen($query) < 2)
            <p class="text-tnf-muted">Enter at least 2 characters to search.</p>
        @elseif($articles->isEmpty() && $videos->isEmpty() && $epapers->isEmpty())
            <div class="rounded-tnf-lg bg-white p-8 text-center shadow-card">
                <p class="text-tnf-muted">No results for &ldquo;{{ $query }}&rdquo;.</p>
            </div>
        @else
            @if($articles->count() > 0)
                <section class="mb-10">
                    <h2 class="mb-4 text-tnf-lg font-bold text-tnf-navy">News ({{ $articles->total() }})</h2>
                    <div class="tnf-cat-block-grid">
                        @foreach($articles as $article)
                            <x-cards.news-card :article="$article" />
                        @endforeach
                    </div>
                    <div class="mt-8">{{ $articles->links() }}</div>
                </section>
            @endif

            @if($videos->isNotEmpty())
                <section class="mb-10">
                    <h2 class="mb-4 text-tnf-lg font-bold text-tnf-navy">Videos</h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($videos as $video)
                            <x-cards.video-card :video="$video" />
                        @endforeach
                    </div>
                </section>
            @endif

            @if($epapers->isNotEmpty())
                <section>
                    <h2 class="mb-4 text-tnf-lg font-bold text-tnf-navy">ePaper</h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($epapers as $edition)
                            <x-cards.epaper-card :edition="$edition" />
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </div>
</x-site.layout>
