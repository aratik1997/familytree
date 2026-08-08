{{-- Both layouts pull this in, so the tab icon cannot drift between the
     signed-in pages and the sign-in door.

     Order matters. The .ico comes first as the floor every browser reads, and
     carries hand-drawn 16, 32 and 48 versions rather than one image scaled
     down. The .svg follows: anything that understands it prefers it and gets a
     sharp icon at any size, ignoring the .ico entirely.

     Versioned, because a favicon is about the most aggressively cached file a
     site has — without it, anyone who visited while public/favicon.ico was
     still the empty placeholder would go on seeing nothing. --}}
@php($v = '2026-08-07')

<link rel="icon" href="{{ url('favicon.ico?v='.$v) }}" type="image/x-icon" sizes="16x16 32x32 48x48">
<link rel="icon" href="{{ url('favicon.svg?v='.$v) }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ url('apple-touch-icon.png?v='.$v) }}">
<meta name="theme-color" content="#0B3D2E">
