<x-site.layout :title="$title" :seo="$seo">
    <div class="tnf-page-content">
        <h1 class="tnf-section-title mb-2">{{ $heading }}</h1>
        @if(! empty($description))
            <p class="mb-6 text-tnf-sm text-tnf-muted">{{ $description }}</p>
        @endif

        @if($articles->count() > 0)
            <div class="tnf-cat-block-grid">
                @foreach($articles as $article)
                    <x-cards.news-card :article="$article" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $articles->links() }}
            </div>
        @else
            <div class="rounded-tnf-lg bg-white p-8 text-center shadow-card">
                <p class="text-tnf-muted">No articles found in this section yet.</p>
                <a href="{{ route('home') }}" class="tnf-btn-primary mt-4">Back to Home</a>
            </div>
        @endif
    </div>
</x-site.layout>
