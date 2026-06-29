@props(['size' => 'md', 'logo' => null])

@php
    $hasBrandLogo = filled($logo);
    $logoSize = match (true) {
        $hasBrandLogo && $size === 'md' => 'header',
        $hasBrandLogo && $size === 'sm' => 'xs',
        default => $size,
    };
@endphp

<a {{ $attributes->merge([
    'href' => '/',
    'class' => 'tnf-header-logo'.($hasBrandLogo ? ' tnf-header-logo--brand' : ''),
]) }}>
    <x-site.brand-mark
        :size="$logoSize"
        :logo="$logo"
        :show-wordmark="! $hasBrandLogo"
    />
</a>
