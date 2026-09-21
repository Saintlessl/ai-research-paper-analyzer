<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#E8ECF3">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600|source-serif-4:400,400i,600,600i&display=swap" rel="stylesheet" />

        <!-- Anti-FOUC Theme Script -->
        <script>
            (function() {
                try {
                    var storedTheme = localStorage.getItem('theme');
                    var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    var theme = storedTheme === 'dark' || (!storedTheme && prefersDark) ? 'dark' : 'light';
                    
                    if (theme === 'dark') {
                        document.documentElement.classList.add('dark');
                        document.querySelector('meta[name="theme-color"]').setAttribute('content', '#22262E');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                } catch (e) {}
            })();
        </script>

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
