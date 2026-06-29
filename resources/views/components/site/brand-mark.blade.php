@props([
    'size' => 'md',
    'logo' => null,
    'wordmark' => null,
    'showWordmark' => true,
])

@php
    $wordmarkText = $wordmark ?: config('app.name', 'TNF Today');
    $logoPath = filled($logo) ? $logo : null;
    $sizeClass = match ($size) {
        'xs' => 'tnf-brand-logo-image--xs',
        'sm' => 'tnf-brand-logo-image--sm',
        'lg' => 'tnf-brand-logo-image--lg',
        default => 'tnf-brand-logo-image--md',
    };
    $fallbackClass = match ($size) {
        'xs' => 'h-7 w-7 text-xs',
        'sm' => 'h-7 w-7 text-xs',
        'lg' => 'h-12 w-12 text-lg',
        default => 'h-8 w-8 text-sm',
    };
@endphp

@if($logoPath)
    <img
        src="{{ asset('storage/'.$logoPath) }}"
        alt="{{ $wordmarkText }}"
        class="tnf-brand-logo-image {{ $sizeClass }}"
        width="36"
        height="36"
        decoding="async"
        {{ $attributes->only('loading') }}
    >
@else
    <span class="tnf-header-logo-mark {{ $fallbackClass }}">TNF</span>
@endif

@if($showWordmark)
    <span {{ $attributes->class(['tnf-brand-logo-wordmark']) }}>{{ $wordmarkText }}</span>
@endif
