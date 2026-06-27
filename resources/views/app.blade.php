<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Band') }}</title>

        {{-- PWA / installability. Personal member pages override the manifest
             link via Inertia <Head> so the installed iOS app launches already
             identified by its push_token. --}}
        <link rel="manifest" href="/manifest.webmanifest">
        <meta name="theme-color" content="#1296B8">

        {{-- Favicons: SVG preferred; PNG fallbacks for older browsers --}}
        <link rel="icon" href="/icons/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/icons/favicon-32.png" type="image/png" sizes="32x32">
        <link rel="icon" href="/icons/favicon-16.png" type="image/png" sizes="16x16">
        <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="GigWithMe">

        {{-- Set the color scheme before paint so dark mode never flashes white.
             Mirrors the logic in resources/js/composables/useDarkMode.js. --}}
        <script>
            (function () {
                try {
                    var stored = localStorage.getItem('gigwithme-theme');
                    var dark = stored
                        ? stored === 'dark'
                        : window.matchMedia('(prefers-color-scheme: dark)').matches;
                    document.documentElement.classList.toggle('dark', dark);
                } catch (e) {}
            })();
        </script>

        @fonts
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="h-full antialiased bg-canvas text-ink dark:bg-backstage dark:text-canvas">
        @inertia
    </body>
</html>
