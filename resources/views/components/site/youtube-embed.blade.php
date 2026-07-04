@props([
    'src',
    'title' => '',
    'portrait' => false,
])

<div @class([
    'tnf-article-embed overflow-hidden rounded-tnf-lg shadow-card',
    'mt-6 aspect-[9/16] max-h-[80vh] sm:aspect-video sm:max-h-none' => $portrait,
    'mt-6 aspect-video' => ! $portrait,
])>
    <iframe
        src="{{ $src }}"
        title="{{ $title }}"
        class="h-full w-full border-0"
        loading="lazy"
        referrerpolicy="strict-origin-when-cross-origin"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        allowfullscreen
    ></iframe>
</div>
