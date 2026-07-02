@props(['favicon' => ''])

@php
    $faviconPath = filled($favicon) ? (string) $favicon : '';
    $faviconUrl = filled($faviconPath) ? asset('storage/'.$faviconPath) : asset('favicon.svg');
    $extension = strtolower(pathinfo($faviconPath, PATHINFO_EXTENSION));
    $faviconType = match ($extension) {
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'ico' => 'image/x-icon',
        default => 'image/svg+xml',
    };
    $touchIconUrl = filled($faviconPath) && in_array($extension, ['png', 'svg'], true)
        ? $faviconUrl
        : asset('apple-touch-icon.svg');
@endphp

<link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
@if(filled($faviconPath))
    <link rel="alternate icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
@else
    <link rel="alternate icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
@endif
<link rel="apple-touch-icon" href="{{ $touchIconUrl }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
