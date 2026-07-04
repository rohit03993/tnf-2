@props(['favicon' => ''])

@php
    use App\Models\Setting;
    use App\Services\PwaIconService;

    $faviconPath = filled($favicon) ? (string) $favicon : '';
    $faviconUrl = filled($faviconPath) ? asset('storage/'.$faviconPath) : asset('favicon.svg');
    $extension = strtolower(pathinfo($faviconPath, PATHINFO_EXTENSION));
    $faviconType = match ($extension) {
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'ico' => 'image/x-icon',
        default => 'image/svg+xml',
    };
    $pwaIconPath = (string) Setting::get('pwa_icon', '');
    $hasPwaIcon = filled($pwaIconPath) && PwaIconService::url($pwaIconPath);
    $touchIconUrl = $hasPwaIcon
        ? route('pwa.icon', ['size' => 192])
        : (filled($faviconPath) && in_array($extension, ['png', 'svg'], true)
            ? $faviconUrl
            : asset('apple-touch-icon.svg'));
@endphp

<link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
@if(filled($faviconPath))
    <link rel="alternate icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
@else
    <link rel="alternate icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
@endif
<link rel="apple-touch-icon" href="{{ $touchIconUrl }}">
<link rel="manifest" href="{{ route('manifest') }}">
