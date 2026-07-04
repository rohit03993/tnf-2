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
    <div class="tnf-epaper-flip">
        <div class="tnf-epaper-flip__inner">
            <div class="tnf-epaper-flip__face tnf-epaper-flip__face--front">
                <img
                    src="{{ $thumbUrl }}"
                    alt=""
                    class="tnf-epaper-thumb tnf-epaper-thumb--header"
                    loading="eager"
                    decoding="async"
                    width="32"
                    height="42"
                >
            </div>
            <div class="tnf-epaper-flip__face tnf-epaper-flip__face--back" aria-hidden="true">
                <span class="tnf-epaper-flip__back-mark">TNF</span>
            </div>
        </div>
    </div>
@elseif($variant === 'nav')
    @if($thumbUrl)
        <img
            src="{{ $thumbUrl }}"
            alt=""
            class="tnf-epaper-thumb tnf-epaper-thumb--nav"
            loading="lazy"
            decoding="async"
            width="20"
            height="26"
        >
    @else
        <x-site.bottom-nav-icon name="epaper" />
    @endif
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
