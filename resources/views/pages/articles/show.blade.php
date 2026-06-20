@php
    use App\Support\Embed;
    $embedSrc = Embed::youtubeIframeSrc($article->embed_url);
@endphp

<x-site.layout :title="$article->title" :seo="$seo" :compact-chrome="true">
    <article class="tnf-page-content tnf-article-page">
        <div class="mx-auto max-w-3xl">
            <header class="tnf-article-header">
                @if($article->categories->isNotEmpty())
                    <nav class="tnf-article-categories" aria-label="Categories">
                        @foreach($article->categories as $category)
                            <a href="{{ route('category.show', $category->slug) }}" class="tnf-article-category">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </nav>
                @endif

                <h1 class="tnf-article-title">{{ $article->title }}</h1>

                <x-site.author-byline :user="$article->author" :published-at="$article->published_at" />
            </header>

            @if($article->featuredMedia?->url())
                <x-site.media-frame
                    variant="article"
                    :image-url="$article->featuredMedia->url()"
                    :alt="$article->title"
                    class="mt-5"
                />
            @endif

            @if($embedSrc)
                <div class="tnf-article-embed mt-6 aspect-video overflow-hidden rounded-tnf-lg shadow-card">
                    <iframe
                        src="{{ $embedSrc }}"
                        title="{{ $article->title }}"
                        class="h-full w-full"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                </div>
            @endif

            <div class="prose prose-tnf tnf-article-body mt-6">
                {!! $article->content !!}
            </div>

            <div class="mt-8">
                <x-site.share-bar :title="$article->title" />
            </div>
        </div>

        @if($relatedArticles->isNotEmpty())
            <section class="mt-10 tnf-cat-block">
                <h2 class="tnf-section-title mb-4">Related News</h2>
                <div class="tnf-cat-block-grid">
                    @foreach($relatedArticles as $related)
                        <x-cards.news-card :article="$related" />
                    @endforeach
                </div>
            </section>
        @endif
    </article>
</x-site.layout>
