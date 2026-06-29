<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0F1320">

    <title>@yield('title', config('app.name', 'TNF Today'))</title>

    <x-site.fonts />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body
    class="{{ ($authLite ?? false) ? 'tnf-auth-lite' : '' }} {{ ($hideBottomNav ?? false) ? 'tnf-no-bottom-nav' : '' }}"
    x-data="tnfSite()"
>
    @unless($authLite ?? false)
        {{-- Phase E: header, ticker, drawer, bottom nav --}}
    @endunless

    <main id="tnf-main">
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    @stack('scripts')
</body>
</html>
