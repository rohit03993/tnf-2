@props(['article', 'featured' => false])

@php
    $url = route('article.show', $article->slug);
    $imageUrl = $article->featuredMedia?->url();
@endphp

<article {{ $attributes->merge(['class' => $featured ? 'tnf-hero-lead' : 'tnf-news-card']) }}>
    <a href="{{ $url }}" class="block">
        <x-site.media-frame
            :variant="$featured ? 'hero' : 'card'"
            :image-url="$imageUrl"
            :alt="$article->title"
            :loading="$featured ? 'eager' : 'lazy'"
            :fetchpriority="$featured ? 'high' : null"
            @class(['rounded-none' => $featured])
        />
        <div class="tnf-news-card-body">
            <h{{ $featured ? '2' : '3' }} class="tnf-news-card-title tnf-line-clamp-3">{{ $article->title }}</h{{ $featured ? '2' : '3' }}>
            <p class="tnf-news-card-meta">
                {{ \App\Support\NewsDate::formatDate($article->published_at) }}
            </p>
        </div>
    </a>
</article>
