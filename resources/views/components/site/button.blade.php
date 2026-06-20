@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
])

@php
    $classes = match ($variant) {
        'outline' => 'tnf-btn-outline',
        'ghost' => 'tnf-btn-ghost',
        default => 'tnf-btn-primary',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
