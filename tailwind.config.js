import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Skala warna tunggal Marian Blue (#263C92 = marian-800), dipakai sebagai aksen.
 * "indigo" diarahkan ke skala yang sama supaya semua komponen bawaan (Breeze)
 * otomatis memakai biru lab, tanpa warna lain.
 */
const marian = {
    50: '#EEF1FB',
    100: '#DDE3F6',
    200: '#BCC7EE',
    300: '#93A5E2',
    400: '#6A82D4',
    500: '#4863C4',
    600: '#3450B0',
    700: '#2C459F',
    800: '#263C92',
    900: '#1E2F72',
    950: '#131E4A',
};

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
                marian,
                indigo: marian,
            },
            fontFamily: {
                sans: ['"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            boxShadow: {
                // Gaya lab bersih: tanpa bayangan lembut besar, cukup garis tipis
                lembut: '0 1px 0 rgba(19, 30, 74, 0.06)',
            },
        },
        // Sudut tajam ala instrumen lab: semua skala rounded maksimal 4px (kecuali full)
        borderRadius: {
            none: '0',
            sm: '2px',
            DEFAULT: '2px',
            md: '3px',
            lg: '3px',
            xl: '4px',
            '2xl': '4px',
            '3xl': '4px',
            '4xl': '4px',
            '5xl': '4px',
            full: '9999px',
        },
    },

    plugins: [forms],
};
