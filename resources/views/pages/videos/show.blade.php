@php
    use App\Support\Embed;
    $embedSrc = Embed::youtubeIframeSrc($video->embed_url);
@endphp

<x-site.layout :title="$video->title" :seo="$seo" :compact-chrome="true">
    <article class="tnf-page-content tnf-article-page">
        <div class="mx-auto max-w-3xl">
            <header class="tnf-article-header">
                @if($video->categories->isNotEmpty())
                    <nav class="tnf-article-categories" aria-label="Categories">
                        @foreach($video->categories as $category)
                            <a href="{{ route('category.show', $category->slug) }}" class="tnf-article-category">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </nav>
                @endif

                <h1 class="tnf-article-title">{{ $video->title }}</h1>

                <x-site.author-byline :user="$video->author" :published-at="$video->published_at" />
            </header>

            @if($embedSrc)
                <x-site.youtube-embed :src="$embedSrc" :title="$video->title" portrait />
            @elseif($video->featuredMedia?->url())
                <x-site.media-frame
                    variant="article"
                    :image-url="$video->featuredMedia->url()"
                    :alt="$video->title"
                    class="mt-5"
                />
            @endif

            @if($video->content)
                <div class="prose prose-tnf tnf-article-body mt-6">
                    {!! $video->content !!}
                </div>
            @endif

            <div class="mt-8">
                <x-site.share-bar :title="$video->title" />
            </div>
        </div>
    </article>
</x-site.layout>
