<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        <script>
            window.__APP_REVERB__ = {
                key: @json(env('REVERB_APP_KEY')),
                host: @json(env('VITE_REVERB_HOST', env('REVERB_HOST', request()->getHost()))),
                port: @json((int) env('VITE_REVERB_PORT', env('REVERB_PORT', 8080))),
                scheme: @json(env('VITE_REVERB_SCHEME', env('REVERB_SCHEME', 'http'))),
            };
        </script>
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
