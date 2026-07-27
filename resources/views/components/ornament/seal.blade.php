@props(['size' => 88])

{{--
    The Seal — this theme's one signature element. An engraved gold disc
    carrying the family name around its rim in both scripts, turning once
    every four minutes: slow enough that you only catch it if you watch.

    On the tree it recentres the view on the root ancestor and sends a gold
    shimmer up the trunk. Anywhere else it simply leads to the tree.

    No screen should introduce a second focal ornament to compete with it.
--}}
<button type="button"
        data-seal
        class="seal group relative shrink-0"
        style="width: {{ $size }}px; height: {{ $size }}px"
        aria-label="{{ __('Recentre the tree on the eldest ancestor') }}">
    <svg viewBox="0 0 100 100" width="{{ $size }}" height="{{ $size }}"
         role="presentation" focusable="false"
         fill="none" stroke="currentColor" style="color: var(--gold-500)">
        <defs>
            {{-- Rim baseline for the running text. Split into two arcs so the
                 lower label reads left-to-right rather than upside down. --}}
            <path id="seal-rim-top" d="M 50 50 m -37 0 a 37 37 0 1 1 74 0" />
            <path id="seal-rim-bottom" d="M 50 50 m -31 0 a 31 31 0 1 0 62 0" />
        </defs>

        {{-- Engraved rings --}}
        <circle cx="50" cy="50" r="47" stroke-width="1" opacity=".55" />
        <circle cx="50" cy="50" r="43" stroke-width="1.6" />
        <circle cx="50" cy="50" r="24" stroke-width="1" opacity=".75" />

        {{-- Rim text + tick marks turn together --}}
        <g class="seal-spin" style="transform-origin: 50px 50px">
            <text font-size="7.5" letter-spacing="2.6" fill="currentColor" stroke="none"
                  style="font-family: var(--font-display); font-weight: 700">
                <textPath href="#seal-rim-top" startOffset="50%" text-anchor="middle">
                    THE KHANDANI LEGACY
                </textPath>
            </text>
            <text font-size="7" letter-spacing="1.2" fill="currentColor" stroke="none" lang="bn"
                  style="font-family: 'Noto Serif Bengali', var(--font-display); font-weight: 600">
                <textPath href="#seal-rim-bottom" startOffset="50%" text-anchor="middle">
                    খানদানি লিগ্যাসি
                </textPath>
            </text>

            <g stroke-width="1" opacity=".5">
                @for ($i = 0; $i < 24; $i++)
                    <line x1="50" y1="4" x2="50" y2="7"
                          transform="rotate({{ $i * 15 }} 50 50)" />
                @endfor
            </g>
        </g>

        {{-- Static heart: a stylised tree inside the inner ring --}}
        <g stroke-width="1.4" stroke-linecap="round">
            <path d="M50 64 V 46" />
            <path d="M50 52 C 44 50, 41 46, 41 41" />
            <path d="M50 52 C 56 50, 59 46, 59 41" />
            <path d="M50 46 C 46 43, 45 39, 46 35" opacity=".8" />
            <path d="M50 46 C 54 43, 55 39, 54 35" opacity=".8" />
            <circle cx="41" cy="40" r="2" fill="currentColor" stroke="none" opacity=".9" />
            <circle cx="59" cy="40" r="2" fill="currentColor" stroke="none" opacity=".9" />
            <circle cx="50" cy="33" r="2.2" fill="currentColor" stroke="none" />
            <path d="M43 64 H 57" opacity=".6" />
        </g>
    </svg>
</button>
