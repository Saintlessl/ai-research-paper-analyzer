import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.tsx',
    ],

    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', '"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                serif: ['"Source Serif 4"', 'Newsreader', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                neu: {
                    bg: 'var(--neu-bg)',
                    surface: 'var(--neu-surface)',
                    text: 'var(--neu-text)',
                    muted: 'var(--neu-text-muted)',
                    hairline: 'var(--neu-hairline)',
                    'accent-fill': 'var(--neu-accent-fill)',
                    'accent-text': 'var(--neu-accent-text)',
                    'accent-on': 'var(--neu-accent-on)',
                },
                status: {
                    uploaded: { fill: 'var(--color-status-uploaded-fill)', text: 'var(--color-status-uploaded-text)', on: 'var(--color-status-uploaded-on)' },
                    processing: { fill: 'var(--color-status-processing-fill)', text: 'var(--color-status-processing-text)', on: 'var(--color-status-processing-on)' },
                    analyzed: { fill: 'var(--color-status-analyzed-fill)', text: 'var(--color-status-analyzed-text)', on: 'var(--color-status-analyzed-on)' },
                    failed: { fill: 'var(--color-status-failed-fill)', text: 'var(--color-status-failed-text)', on: 'var(--color-status-failed-on)' },
                },
                severity: {
                    low: { fill: 'var(--color-severity-low-fill)', text: 'var(--color-severity-low-text)', on: 'var(--color-severity-low-on)' },
                    medium: { fill: 'var(--color-severity-medium-fill)', text: 'var(--color-severity-medium-text)', on: 'var(--color-severity-medium-on)' },
                    high: { fill: 'var(--color-severity-high-fill)', text: 'var(--color-severity-high-text)', on: 'var(--color-severity-high-on)' },
                    critical: { fill: 'var(--color-severity-critical-fill)', text: 'var(--color-severity-critical-text)', on: 'var(--color-severity-critical-on)' },
                }
            },
            borderRadius: {
                'neu-sm': 'var(--neu-radius-sm)',
                'neu-md': 'var(--neu-radius-md)',
                'neu-lg': 'var(--neu-radius-lg)',
                'neu-pill': 'var(--neu-radius-pill)',
            },
            zIndex: {
                dropdown: 'var(--z-dropdown)',
                tooltip: 'var(--z-tooltip)',
                drawer: 'var(--z-drawer)',
                modal: 'var(--z-modal)',
                toast: 'var(--z-toast)',
            }
        },
    },

    plugins: [forms],
};
