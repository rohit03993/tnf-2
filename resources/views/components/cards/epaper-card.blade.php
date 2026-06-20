@props(['edition'])

@php
    $url = route('epaper.show', $edition->slug);
    $imageUrl = $edition->coverImageUrl();
    $pdfUrl = $imageUrl ? null : $edition->pdfPublicUrl();
@endphp

<article class="tnf-epaper-card">
    <a href="{{ $url }}" class="block">
        <div class="tnf-epaper-card-cover-frame">
            @if($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $edition->title }}" class="tnf-epaper-card-cover" loading="lazy">
            @elseif($pdfUrl)
                <div
                    class="tnf-epaper-card-cover tnf-epaper-card-cover--pdf tnf-epaper-card-cover--loading"
                    data-ep-pdf-cover
                    data-pdf-url="{{ $pdfUrl }}"
                    role="img"
                    aria-label="{{ $edition->title }}"
                ></div>
            @else
                <div class="tnf-epaper-card-cover tnf-skeleton" aria-hidden="true"></div>
            @endif
        </div>
        <div class="tnf-news-card-body">
            <h3 class="tnf-news-card-title tnf-line-clamp-2">{{ $edition->title }}</h3>
            <p class="tnf-news-card-meta">{{ $edition->published_at?->format('M d, Y') }}</p>
        </div>
    </a>
</article>
