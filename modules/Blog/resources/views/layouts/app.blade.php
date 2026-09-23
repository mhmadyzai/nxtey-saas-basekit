<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Blog</title>

    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="{{ config('themer.active') === 'alpha' ? '#059669' : '#4f46e5' }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <link rel="apple-touch-icon" href="/themes/{{ config('themer.active') }}/pwa/icon-192.png">

    <link rel="stylesheet" href="{{ theme_asset('css/app.css') }}">
	<script src="{{ theme_asset('js/app.js') }}" defer></script>
    @livewireStyles
</head>
<body>
    {{ $slot }}
    @livewireScripts

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js', { scope: '/' })
                    .then((reg) => console.log('SW registered:', reg.scope))
                    .catch((err) => console.warn('SW registration failed', err));
            });
        }
    </script>
</body>
</html>