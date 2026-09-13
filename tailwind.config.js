import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                nt: {
                    ink: '#0c1a12',
                    soft: '#1a2e22',
                    leaf: '#1a8a52',
                    deep: '#0f5c36',
                    bright: '#34c878',
                    mist: '#e6efe8',
                    surface: '#f5f9f6',
                    muted: '#5c7264',
                    honey: '#c9922a',
                },
            },
            fontFamily: {
                sans: ['Sora', ...defaultTheme.fontFamily.sans],
                display: ['Fraunces', ...defaultTheme.fontFamily.serif],
            },
            boxShadow: {
                nt: '0 1px 0 rgba(12,26,18,0.04), 0 12px 32px -16px rgba(12,26,18,0.18)',
                'nt-lg': '0 1px 0 rgba(12,26,18,0.04), 0 24px 48px -20px rgba(12,26,18,0.28)',
            },
            borderRadius: {
                nt: '1.25rem',
            },
            transitionTimingFunction: {
                nt: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
        },
    },

    plugins: [forms],
};
