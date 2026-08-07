<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        {{-- Theme is resolved before first paint so a light-theme visitor
             never sees a dark flash. --}}
        <script>
            (function () {
                var stored = localStorage.getItem('theme');
                if (stored === 'light' || stored === 'dark') {
                    document.documentElement.setAttribute('data-theme', stored);
                }
            })();
        </script>

        <script>
            window.APP_URL = @json(rtrim(config('app.url'), '/'));
            window.IS_SUPER_ADMIN = @json((bool) auth()->user()?->managesTree());
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <x-ornament.damask />

        <div class="relative z-10 min-h-screen">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="hairline-b" style="background: var(--ink-700)">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="pb-[env(safe-area-inset-bottom)]">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
