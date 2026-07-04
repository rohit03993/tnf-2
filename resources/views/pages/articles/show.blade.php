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
                <x-site.youtube-embed :src="$embedSrc" :title="$article->title" />
            @endif

            <x-site.article-readers :article="$article" />

            <div class="prose prose-tnf tnf-article-body mt-6" id="tnf-reading-target">
                {!! $article->content !!}
            </div>

            <div class="mt-8 hidden lg:block">
                <x-site.share-bar :title="$article->title" :url="route('article.show', $article)" />
            </div>
        </div>

        @if($relatedArticles->isNotEmpty())
            <section class="tnf-article-related mx-auto mt-10 max-w-3xl lg:max-w-none">
                <h2 class="tnf-section-title mb-4">Related News</h2>
                <div class="tnf-cat-block-grid">
                    @foreach($relatedArticles as $related)
                        <x-cards.news-card :article="$related" />
                    @endforeach
                </div>
            </section>
        @endif
    </article>

    <div class="tnf-share-sticky-wrap lg:hidden">
        <x-site.share-bar :title="$article->title" :url="route('article.show', $article)" class="tnf-share-bar--compact" />
    </div>
</x-site.layout>
