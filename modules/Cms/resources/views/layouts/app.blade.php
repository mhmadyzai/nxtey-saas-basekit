<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ➡️ SEO & META BLOCK (FIXED WITH NULL-SAFE OPERATORS) -->
    @php
        $entry = $entry ?? null; // Avoid undefined variable crash if not passed from controller
        
        $seoTitle = $entry?->seo_title ?? $entry?->title ?? config('app.name');
        $seoDescription = $entry?->seo_description ?? '';
        $ogImage = $entry?->og_image_id ? media_url($entry->og_image_id) : null;
        $canonical = $entry?->canonical_url ?? url()->current();
    @endphp

    <title>{{ $seoTitle }} — {{ tenant()?->getTenantKey() }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <link rel="canonical" href="{{ $canonical }}">

    @if($entry?->no_index ?? false)
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="robots" content="index, follow">
    @endif


    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $canonical }}">
    @if($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    @if($ogImage)
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif
    <!-- ➡️ END OF SEO BLOCK -->

    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="{{ config('themer.active') === 'alpha' ? '#059669' : '#4f46e5' }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="/themes/{{ config('themer.active') }}/pwa/icon-192.png">

    <link rel="stylesheet" href="{{ theme_asset('css/app.css') }}">
    <script src="{{ theme_asset('js/app.js') }}" defer></script>
    @livewireStyles
</head>
<body>
	<header>
        <x-cms::menu location="header" />
    </header>
    <main>
        {{ $slot }}
    </main>
	<footer>
        <x-cms::menu location="footer" />
    </footer>
    @livewireScripts

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js', { scope: '/' })
                    .catch((err) => console.warn('SW registration failed', err));
            });
        }
    </script>
</body>
</html>