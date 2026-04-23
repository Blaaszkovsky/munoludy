import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Http/Controllers/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                'munoludy-bg': 'var(--munoludy-bg)',
                'munoludy-text': 'var(--munoludy-text)',
                'munoludy-pink': 'var(--munoludy-pink)',
                'munoludy-btn-bg': 'var(--munoludy-button-bg)',
                'munoludy-btn-text': 'var(--munoludy-button-text)',
            },
            fontFamily: {
                heading: ['var(--font-heading)', ...defaultTheme.fontFamily.sans],
                body: ['var(--font-body)', ...defaultTheme.fontFamily.sans],
                sans: ['var(--font-body)', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [forms, typography],
};
