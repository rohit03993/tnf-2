@props(['article'])

@php
    $url = route('article.show', $article);
    $imageUrl = $article->featuredMedia?->url();
@endphp

<div class="tnf-trending-item">
    <x-site.media-frame variant="thumb" :image-url="$imageUrl" alt="" />
    <a href="{{ $url }}" class="tnf-trending-title tnf-line-clamp-3">{{ $article->title }}</a>
</div>
