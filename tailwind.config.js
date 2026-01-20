import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Arial', 'Helvetica', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Tactical Color Palette - POLDA JATENG Theme
                'navy': {
                    DEFAULT: '#001f3f',
                    50: '#e6f0ff',
                    100: '#b3d1ff',
                    200: '#80b3ff',
                    300: '#4d94ff',
                    400: '#1a75ff',
                    500: '#0066ff',
                    600: '#0052cc',
                    700: '#003d99',
                    800: '#002966',
                    900: '#001f3f',
                },
                'slate-grey': {
                    DEFAULT: '#708090',
                    light: '#8899a6',
                    dark: '#4a5568',
                },
                'tactical': {
                    bg: '#f8fafc',
                    border: '#e2e8f0',
                    accent: '#3b82f6',
                    success: '#10b981',
                    warning: '#f59e0b',
                    danger: '#ef4444',
                }
            },
            boxShadow: {
                'tactical': '0 4px 6px -1px rgba(0, 31, 63, 0.1), 0 2px 4px -1px rgba(0, 31, 63, 0.06)',
            },
        },
    },

    plugins: [forms],
};
