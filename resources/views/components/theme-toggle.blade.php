{{--
    Dark is the default; light is the parchment variant. The choice is stored
    locally and applied before first paint by the inline script in the layout.
--}}
<button type="button"
        x-data="{
            theme: document.documentElement.getAttribute('data-theme') || 'dark',
            toggle() {
                this.theme = this.theme === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', this.theme);
                localStorage.setItem('theme', this.theme);
            },
        }"
        @click="toggle()"
        class="inline-flex items-center justify-center rounded-control transition duration-micro ease-royal"
        style="width: 44px; height: 44px; color: var(--text-mid)"
        :aria-label="theme === 'dark' ? '{{ __('Switch to light theme') }}' : '{{ __('Switch to dark theme') }}'">
    {{-- Moon (shown while dark) --}}
    <svg x-show="theme === 'dark'" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
    </svg>

    {{-- Sun (shown while light) --}}
    <svg x-show="theme === 'light'" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="4" />
        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" />
    </svg>
</button>
