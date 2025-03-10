<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="text-zinc-950 antialiased lg:bg-zinc-100 dark:bg-zinc-900 dark:text-white dark:lg:bg-zinc-950">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <script>
            (function() {
                const theme = localStorage.getItem('theme');
                if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://rsms.me">
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
