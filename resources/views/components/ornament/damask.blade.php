{{--
    Damask backdrop — a tiled lotus/paisley motif sitting behind the whole
    canvas at 4% opacity. It should be barely perceptible: if the motif is
    nameable at a glance, the opacity is too high.

    Drawn as a live SVG rather than a data URI so its ink comes from the gold
    token and follows the theme.
--}}
<svg class="damask" aria-hidden="true" focusable="false"
     width="100%" height="100%" preserveAspectRatio="xMidYMid slice"
     style="color: var(--gold-500)"
     xmlns="http://www.w3.org/2000/svg">
    <defs>
        <pattern id="damask-tile" width="160" height="160" patternUnits="userSpaceOnUse">
            <g fill="none" stroke="currentColor" stroke-width="1.2">
                {{-- Lotus rosette --}}
                <circle cx="80" cy="80" r="26" />
                <path d="M80 54 C 92 66, 92 94, 80 106 C 68 94, 68 66, 80 54 Z" />
                <path d="M54 80 C 66 68, 94 68, 106 80 C 94 92, 66 92, 54 80 Z" />
                <path d="M62 62 C 78 68, 92 82, 98 98 C 82 92, 68 78, 62 62 Z" />
                <path d="M98 62 C 92 78, 78 92, 62 98 C 68 82, 82 68, 98 62 Z" />

                {{-- Paisley corners, which meet across the tile seams --}}
                <path d="M0 0 C 14 6, 20 18, 14 30 C 8 22, 4 12, 0 0 Z" />
                <path d="M160 0 C 146 6, 140 18, 146 30 C 152 22, 156 12, 160 0 Z" />
                <path d="M0 160 C 14 154, 20 142, 14 130 C 8 138, 4 148, 0 160 Z" />
                <path d="M160 160 C 146 154, 140 142, 146 130 C 152 138, 156 148, 160 160 Z" />

                {{-- Connecting vines --}}
                <path d="M0 80 H 44 M 116 80 H 160 M 80 0 V 44 M 80 116 V 160" />
            </g>
        </pattern>
    </defs>
    <rect width="100%" height="100%" fill="url(#damask-tile)" />
</svg>
