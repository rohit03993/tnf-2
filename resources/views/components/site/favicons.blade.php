@props(['favicon' => ''])

@php
    use App\Services\PwaManifestService;
    use Illuminate\Support\Facades\Storage;

    $pwaIconPath = PwaManifestService::iconSourcePath(512);
    $hasPwaIcon = $pwaIconPath !== null && Storage::disk('public')->exists($pwaIconPath);
    $iconVersion = PwaManifestService::iconVersion();

    if ($hasPwaIcon) {
        $faviconUrl = PwaManifestService::iconUrl(32);
        $faviconType = 'image/png';
        $touchIconUrl = PwaManifestService::iconUrl(192);
    } else {
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
    }
@endphp

<link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}" sizes="32x32">
@if($hasPwaIcon)
    <link rel="icon" href="{{ PwaManifestService::iconUrl(192) }}" type="image/png" sizes="192x192">
@elseif(filled($favicon ?? ''))
    <link rel="alternate icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
@else
    <link rel="alternate icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
@endif
<link rel="apple-touch-icon" href="{{ $touchIconUrl }}">
<link rel="manifest" href="{{ route('manifest') }}?v={{ $iconVersion }}">
