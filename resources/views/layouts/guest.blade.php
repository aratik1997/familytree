<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        <script>
            (function () {
                var stored = localStorage.getItem('theme');
                if (stored === 'light' || stored === 'dark') {
                    document.documentElement.setAttribute('data-theme', stored);
                }
            })();
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <x-ornament.damask />

        {{-- A faint gold light source from the upper left, matching the tree
             canvas, so the sign-in door feels like the same room. --}}
        <div class="fixed inset-0 z-0 pointer-events-none"
             style="background:
                radial-gradient(60rem 40rem at 12% -10%, color-mix(in srgb, var(--gold-500) 12%, transparent), transparent 70%),
                radial-gradient(50rem 40rem at 100% 100%, color-mix(in srgb, var(--ink-500) 55%, transparent), transparent 70%)">
        </div>

        <div class="relative z-10 min-h-screen flex flex-col sm:justify-center items-center pt-10 sm:pt-0 px-4 pb-[env(safe-area-inset-bottom)]">
            <div class="flex flex-col items-center gap-3">
                <a href="/" aria-label="{{ __('The Khandani Legacy') }}">
                    <x-application-logo class="w-16 h-16" style="color: var(--gold-500)" />
                </a>
                <span class="wordmark text-2xl">The Khandani Legacy</span>
            </div>

            <div class="relative w-full sm:max-w-md mt-8 px-6 py-6 card">
                <x-ornament.filigree corner="tl" />
                <x-ornament.filigree corner="br" />

                {{ $slot }}
            </div>

            <x-language-toggle class="mt-6" />
        </div>
    </body>
</html>
