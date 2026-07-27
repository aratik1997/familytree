@props(['corner' => 'tl'])

{{--
    Corner filigree — a small Mughal-arch mark, placed on the top-left and
    bottom-right only (never all four). Reserved for the profile hero card
    and modals.
--}}
@php
    $placement = $corner === 'br'
        ? 'bottom-0 right-0 rotate-180'
        : 'top-0 left-0';
@endphp

<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="24" height="24"
     class="pointer-events-none absolute {{ $placement }}"
     style="color: var(--gold-500); opacity: .5"
     fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round">
    {{-- Arch --}}
    <path d="M2 22 V 10 C 2 5, 6 2, 11 2 H 22" />
    {{-- Inner echo --}}
    <path d="M6 22 V 11 C 6 8, 8 6, 11 6 H 18" opacity=".7" />
    {{-- Bud terminal --}}
    <path d="M2 22 q 3 -3 6 0" opacity=".55" />
    <circle cx="11" cy="2" r="1" fill="currentColor" stroke="none" />
</svg>
