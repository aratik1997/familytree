import forms from '@tailwindcss/forms';

/**
 * Every colour here resolves to a token in resources/css/tokens.css — no
 * literal values live in this file or in any component. The `<alpha-value>`
 * form lets Tailwind's opacity modifiers (`border-gold/40`) keep working.
 */
const token = (name) => `rgb(var(--${name}-rgb) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Hind Siliguri', 'system-ui', 'sans-serif'],
                serif: ['Cormorant Garamond', 'Noto Serif Bengali', 'serif'],
                display: ['Cormorant Garamond', 'Noto Serif Bengali', 'serif'],
                mono: ['JetBrains Mono', 'ui-monospace', 'monospace'],
            },

            fontSize: {
                xs: ['0.75rem', { lineHeight: '1.5' }],
                sm: ['0.875rem', { lineHeight: '1.6' }],
                base: ['1rem', { lineHeight: '1.6' }],
                lg: ['1.125rem', { lineHeight: '1.5' }],
                xl: ['1.375rem', { lineHeight: '1.3' }],
                '2xl': ['1.75rem', { lineHeight: '1.1', letterSpacing: '-0.02em' }],
                '3xl': ['2.25rem', { lineHeight: '1.1', letterSpacing: '-0.02em' }],
                '4xl': ['3rem', { lineHeight: '1.1', letterSpacing: '-0.02em' }],
                '5xl': ['4rem', { lineHeight: '1.1', letterSpacing: '-0.02em' }],
            },

            colors: {
                // Surfaces
                surface: {
                    deep: token('ink-900'),
                    DEFAULT: token('ink-800'),
                    raised: token('ink-700'),
                    hover: token('ink-600'),
                    active: token('ink-500'),
                },

                gold: {
                    DEFAULT: token('gold-500'),
                    light: token('gold-300'),
                    dark: token('gold-700'),
                },

                maroon: token('maroon-500'),

                content: {
                    hi: token('text-hi'),
                    mid: token('text-mid'),
                    low: token('text-low'),
                },

                male: token('male-500'),
                female: token('female-500'),
                leaf: token('leaf-600'),

                success: token('success'),
                warning: token('warning'),
                danger: token('danger'),

                // Legacy aliases, so views still mid-migration keep resolving
                // to tokens rather than to Tailwind's stock palette.
                royal: {
                    DEFAULT: token('ink-500'),
                    dark: token('ink-700'),
                    light: token('ink-600'),
                },
                ruby: token('maroon-500'),
                emerald: token('leaf-600'),
                parchment: {
                    DEFAULT: token('ink-800'),
                    dark: token('ink-700'),
                },
                ink: token('text-hi'),
            },

            borderRadius: {
                card: '14px',
                control: '10px',
                modal: '18px',
            },

            boxShadow: {
                card: '0 1px 2px rgb(0 0 0 / .3), 0 8px 24px rgb(0 0 0 / .28)',
                lift: '0 4px 8px rgb(0 0 0 / .32), 0 16px 40px rgb(0 0 0 / .36)',
            },

            transitionTimingFunction: {
                royal: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },

            transitionDuration: {
                micro: '150ms',
                panel: '280ms',
                page: '420ms',
            },
        },
    },

    plugins: [forms],
};
