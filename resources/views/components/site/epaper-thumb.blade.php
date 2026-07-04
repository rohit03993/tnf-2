@props([
    'promo',
    'variant' => 'header',
])

@php
    $title = $promo['title'] ?? 'ePaper';
    $url = $promo['url'] ?? route('epaper.index');
    $thumbUrl = $promo['thumbUrl'] ?? null;
    $pdfUrl = $promo['pdfUrl'] ?? null;
@endphp

@if($variant === 'header')
    <img
        src="{{ $thumbUrl }}"
        alt="{{ $title }}"
        class="tnf-epaper-thumb tnf-epaper-thumb--header"
        loading="eager"
        decoding="async"
        width="32"
        height="42"
    >
@elseif($variant === 'teaser')
    <div class="tnf-epaper-thumb-frame tnf-epaper-thumb-frame--teaser">
        @if($thumbUrl)
            <img
                src="{{ $thumbUrl }}"
                alt="{{ $title }}"
                class="tnf-epaper-thumb tnf-epaper-thumb--teaser"
                loading="eager"
                decoding="async"
            >
        @elseif($pdfUrl)
            <div
                class="tnf-epaper-thumb tnf-epaper-thumb--teaser tnf-epaper-thumb--pdf tnf-epaper-card-cover--loading"
                data-ep-pdf-cover
                data-pdf-url="{{ $pdfUrl }}"
                role="img"
                aria-label="{{ $title }}"
            ></div>
        @endif
    </div>
@endif
