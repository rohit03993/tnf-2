<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#BC1E38">

    <title>{{ config('app.name', 'TNF Today') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;500;600;700&family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/auth.css', 'resources/js/app.js'])
</head>
<body class="tnf-auth-page">
    <div class="tnf-auth-wrap">
        <div class="tnf-auth-card">
            <div class="tnf-auth-logo">
                <div class="tnf-auth-logo-mark">TNF</div>
                <h1 class="tnf-auth-title">{{ config('app.name', 'TNF Today') }}</h1>
                <p class="tnf-auth-subtitle">Sign in to your account</p>
            </div>

            {{ $slot }}
        </div>
    </div>
</body>
</html>
