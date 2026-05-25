<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Band') }}</title>

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
