@props(['size' => 'md'])

@php
    $sizes = [
        'sm' => 'h-7 w-7 text-xs',
        'md' => 'h-8 w-8 text-sm',
        'lg' => 'h-12 w-12 text-lg',
    ];
    $markClass = $sizes[$size] ?? $sizes['md'];
@endphp

<a {{ $attributes->merge(['href' => '/', 'class' => 'tnf-header-logo']) }}>
    <span class="tnf-header-logo-mark {{ $markClass }}">TNF</span>
    <span class="text-tnf-base font-bold text-tnf-navy mobile-sm:text-tnf-sm">TNF Today</span>
</a>
