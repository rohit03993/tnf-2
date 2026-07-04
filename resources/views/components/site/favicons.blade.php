@props(['favicon' => ''])

@php
    use App\Models\Setting;
    use Illuminate\Support\Facades\Storage;

    $pwaIconPath = (string) Setting::get('pwa_icon', '');
    $hasPwaIcon = filled($pwaIconPath) && Storage::disk('public')->exists($pwaIconPath);

    if ($hasPwaIcon) {
        $faviconUrl = route('pwa.icon', ['size' => 32]);
        $faviconType = 'image/png';
        $touchIconUrl = route('pwa.icon', ['size' => 192]);
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
    <link rel="icon" href="{{ route('pwa.icon', ['size' => 192]) }}" type="image/png" sizes="192x192">
@elseif(filled($favicon ?? ''))
    <link rel="alternate icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
@else
    <link rel="alternate icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
@endif
<link rel="apple-touch-icon" href="{{ $touchIconUrl }}">
<link rel="manifest" href="{{ route('manifest') }}">
