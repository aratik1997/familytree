{{--
    Deliberately self-contained: no @vite, no components, no database.

    An error page is shown precisely when something is broken, and half the
    things it could depend on are the things that break. If this extended the
    guest layout it would pull in the Vite manifest, and a missing or stale
    public/build would turn every error into a second, uglier error. Inline
    styles and a hand-rolled SVG cost a few kilobytes and always render.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') &middot; {{ config('app.name') }}</title>
    <style>
        :root {
            --ink-900: #071612;
            --ink-800: #0C2620;
            --ink-600: #17453A;
            --gold-500: #C9A227;
            --gold-300: #E8CE7A;
            --text-hi: #F4F1E8;
            --text-mid: #B9C7BF;
            --text-low: #7E9088;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem calc(1.5rem + env(safe-area-inset-right)) calc(1.5rem + env(safe-area-inset-bottom)) calc(1.5rem + env(safe-area-inset-left));
            background-color: var(--ink-800);
            background-image:
                radial-gradient(60rem 40rem at 12% -10%, rgba(201, 162, 39, 0.12), transparent 70%),
                radial-gradient(50rem 40rem at 100% 100%, rgba(31, 86, 71, 0.55), transparent 70%);
            color: var(--text-hi);
            font-family: 'Inter', 'Hind Siliguri', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        .card {
            width: 100%;
            max-width: 30rem;
            padding: 2.5rem 2rem;
            text-align: center;
            background: rgba(7, 22, 18, 0.55);
            border: 1px solid rgba(201, 162, 39, 0.25);
            border-radius: 0.5rem;
        }

        .crest { color: var(--gold-500); width: 3.5rem; height: 3.5rem; }

        .code {
            margin: 1.25rem 0 0;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            font-size: 0.8125rem;
            letter-spacing: 0.18em;
            color: var(--gold-300);
        }

        h1 {
            margin: 0.5rem 0 0;
            font-family: 'Cormorant Garamond', 'Noto Serif Bengali', Georgia, serif;
            font-size: clamp(1.75rem, 5vw, 2.25rem);
            font-weight: 600;
            letter-spacing: -0.02em;
            line-height: 1.1;
        }

        p { margin: 0.75rem 0 0; color: var(--text-mid); }

        .rule {
            width: 4rem;
            height: 1px;
            margin: 1.5rem auto;
            background: linear-gradient(to right, transparent, rgba(201, 162, 39, 0.6), transparent);
            border: 0;
        }

        a.home {
            display: inline-block;
            padding: 0.625rem 1.5rem;
            border: 1px solid rgba(201, 162, 39, 0.5);
            border-radius: 0.25rem;
            color: var(--gold-300);
            font-size: 0.9375rem;
            text-decoration: none;
            transition: background-color 120ms ease, border-color 120ms ease;
        }

        a.home:hover, a.home:focus-visible {
            background: rgba(201, 162, 39, 0.12);
            border-color: var(--gold-300);
        }

        .ref {
            margin-top: 1.75rem;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            font-size: 0.75rem;
            color: var(--text-low);
        }

        @media (prefers-reduced-motion: reduce) {
            a.home { transition: none; }
        }
    </style>
</head>
<body>
    <main class="card">
        {{-- The same mark as the sign-in door, so a broken page still feels
             like this house rather than a stranger's. --}}
        <svg class="crest" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"
             aria-hidden="true" focusable="false">
            <path d="M12 2 L12 22" />
            <path d="M12 7 C8 7 6 9 6 12" />
            <path d="M12 7 C16 7 18 9 18 12" />
            <circle cx="12" cy="3.5" r="1.5" fill="currentColor" stroke="none" />
            <circle cx="6" cy="13.5" r="1.5" fill="currentColor" stroke="none" />
            <circle cx="18" cy="13.5" r="1.5" fill="currentColor" stroke="none" />
            <circle cx="12" cy="20.5" r="1.5" fill="currentColor" stroke="none" />
        </svg>

        <p class="code">@yield('code')</p>
        <h1>@yield('title')</h1>

        <p>@yield('message')</p>

        <hr class="rule">

        <a class="home" href="{{ url('/') }}">Return to the family tree</a>

        @hasSection('reference')
            <p class="ref">@yield('reference')</p>
        @endif
    </main>
</body>
</html>
