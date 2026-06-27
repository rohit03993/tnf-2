<x-site.layout :title="$title" :seo="$seo">
    <div class="tnf-page-content">
        <x-site.page-header
            :title="$heading"
            :description="$description ?? null"
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('home')],
                ['label' => $heading, 'url' => null],
            ]"
            class="mb-6"
        />

        @if($articles->count() > 0)
            <div class="tnf-archive-grid">
                @foreach($articles as $article)
                    <x-cards.news-card :article="$article" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $articles->links() }}
            </div>
        @else
            <div class="tnf-empty-state">
                <svg class="tnf-empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l6 6v10a2 2 0 01-2 2z"/>
                </svg>
                <p class="tnf-empty-state-title">No articles yet</p>
                <p class="tnf-empty-state-desc">No articles found in this section yet.</p>
                <a href="{{ route('home') }}" class="tnf-btn-primary mt-4">Back to Home</a>
            </div>
        @endif
    </div>
</x-site.layout>
