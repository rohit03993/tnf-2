@props(['category', 'articles'])

<section class="tnf-cat-block">
    <div class="tnf-cat-block-header">
        <h2 class="tnf-section-title tnf-news-content">{{ $category->name }}</h2>
        <a href="{{ route('category.show', $category->slug) }}" class="tnf-see-more-btn">
            See More
        </a>
    </div>
    <div class="tnf-cat-block-grid">
        @foreach($articles as $article)
            <x-cards.news-card :article="$article" />
        @endforeach
    </div>
</section>
