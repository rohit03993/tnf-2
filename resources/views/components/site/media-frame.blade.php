@props([
    'variant' => 'card',
    'imageUrl' => null,
    'alt' => '',
    'loading' => 'lazy',
    'fetchpriority' => null,
])

@php
    $paddingTop = match ($variant) {
        'hero', 'article' => '56.25%',
        'card' => '100%',
        'thumb' => '100%',
        default => '100%',
    };

    $wrapperStyle = match ($variant) {
        'thumb' => 'position:relative;overflow:hidden;background:#E8EAED;width:4rem;flex-shrink:0;border-radius:0.375rem;',
        'article' => 'position:relative;overflow:hidden;background:#E8EAED;width:100%;border-radius:0.5rem;',
        default => 'position:relative;overflow:hidden;background:#E8EAED;width:100%;',
    };

    $wrapperClass = match ($variant) {
        'hero' => 'tnf-frame tnf-frame--hero tnf-hero-media',
        'card' => 'tnf-frame tnf-frame--card',
        'thumb' => 'tnf-frame tnf-frame--thumb',
        'article' => 'tnf-frame tnf-frame--article',
        default => 'tnf-frame',
    };

    $bgPosition = $variant === 'hero' ? 'center top' : 'center';
@endphp

<div {{ $attributes->merge(['class' => $wrapperClass]) }} style="{{ $wrapperStyle }}">
    <div style="display:block;width:100%;height:0;padding-top:{{ $paddingTop }};box-sizing:content-box;"></div>

    @if($imageUrl)
        <div
            style="position:absolute;top:0;left:0;width:100%;height:100%;background-image:url('{{ e($imageUrl) }}');background-size:cover;background-position:{{ $bgPosition }};background-repeat:no-repeat;"
            role="img"
            aria-label="{{ $alt }}"
        ></div>
    @else
        <div style="position:absolute;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#0F1320;">
            <img
                src="{{ asset('favicon.svg') }}"
                alt="{{ $alt }}"
                style="width:52%;height:52%;object-fit:contain;margin:0;max-width:none;"
                loading="{{ $loading }}"
                decoding="async"
            >
        </div>
    @endif
</div>
