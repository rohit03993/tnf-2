@props([
    'class' => '',
    'alt' => 'TNF Today',
    'loading' => 'lazy',
])

<img
    src="{{ asset('favicon.svg') }}"
    alt="{{ $alt }}"
    {{ $attributes->merge(['class' => 'tnf-brand-fallback '.$class]) }}
    loading="{{ $loading }}"
    decoding="async"
>
