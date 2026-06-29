import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './resources/css/**/*.css',
    ],

    theme: {
        extend: {
            colors: {
                tnf: {
                    red: '#BC1E38',
                    'red-dark': '#9E1830',
                    'red-light': '#D42A45',
                    navy: '#0F1320',
                    'navy-light': '#1A2035',
                    'navy-mid': '#252B42',
                    gray: '#F4F5F7',
                    'gray-dark': '#E8EAED',
                    muted: '#6B7280',
                    white: '#FFFFFF',
                },
            },
            fontFamily: {
                sans: ['Halant', '"Noto Sans Devanagari"', '"Noto Sans"', ...defaultTheme.fontFamily.serif],
                devanagari: ['Halant', '"Noto Sans Devanagari"', '"Noto Sans"', ...defaultTheme.fontFamily.serif],
            },
            fontSize: {
                'tnf-xs': ['0.75rem', { lineHeight: '1.25' }],
                'tnf-sm': ['0.875rem', { lineHeight: '1.4' }],
                'tnf-base': ['1rem', { lineHeight: '1.5' }],
                'tnf-lg': ['1.125rem', { lineHeight: '1.4' }],
                'tnf-xl': ['1.25rem', { lineHeight: '1.35' }],
                'tnf-2xl': ['1.5rem', { lineHeight: '1.3' }],
                'tnf-3xl': ['1.875rem', { lineHeight: '1.25' }],
            },
            screens: {
                'mobile-sm': '360px',
                'mobile-md': '393px',
                'mobile-lg': '412px',
            },
            spacing: {
                'bottom-nav': '4.5rem',
                'header': '3.25rem',
                'ticker': '2.25rem',
            },
            minHeight: {
                touch: '44px',
            },
            minWidth: {
                touch: '44px',
            },
            maxWidth: {
                site: '1280px',
            },
            borderRadius: {
                tnf: '0.375rem',
                'tnf-lg': '0.5rem',
            },
            boxShadow: {
                card: '0 1px 3px rgba(15, 19, 32, 0.08)',
                'card-hover': '0 4px 12px rgba(15, 19, 32, 0.12)',
                header: '0 2px 8px rgba(15, 19, 32, 0.15)',
            },
            animation: {
                ticker: 'tnf-ticker 40s linear infinite',
            },
            keyframes: {
                'tnf-ticker': {
                    '0%': { transform: 'translateX(0)' },
                    '100%': { transform: 'translateX(-50%)' },
                },
            },
        },
    },

    plugins: [forms],
};
