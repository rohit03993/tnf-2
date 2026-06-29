<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#BC1E38">

    <title>{{ config('app.name', 'TNF Today') }}</title>

    <x-site.fonts />

    @vite(['resources/css/auth.css', 'resources/js/app.js'])
</head>
<body class="tnf-auth-page">
    <div class="tnf-auth-wrap">
        <div class="tnf-auth-card">
            <div class="tnf-auth-logo">
                @php($siteLogo = \App\Models\Setting::get('site_logo'))
                @if(filled($siteLogo))
                    <x-site.brand-mark :logo="$siteLogo" size="auth" :show-wordmark="false" />
                    <p class="tnf-auth-subtitle">Sign in to your account</p>
                @else
                    <div class="tnf-auth-logo-mark">TNF</div>
                    <h1 class="tnf-auth-title">{{ config('app.name', 'TNF Today') }}</h1>
                    <p class="tnf-auth-subtitle">Sign in to your account</p>
                @endif
            </div>

            {{ $slot }}
        </div>
    </div>
</body>
</html>
