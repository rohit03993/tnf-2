@props(['article', 'number'])

@php
    $url = route('article.show', $article->slug);
    $imageUrl = $article->featuredMedia?->url();
@endphp

<div class="tnf-headline-list-item">
    <span class="tnf-headline-list-num">{{ $number }}</span>
    <x-site.media-frame variant="thumb" :image-url="$imageUrl" alt="" />
    <a href="{{ $url }}" class="tnf-headline-list-title tnf-line-clamp-2">{{ $article->title }}</a>
</div>
