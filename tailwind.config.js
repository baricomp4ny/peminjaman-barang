import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Skala warna tunggal: gradasi Marian Blue (#263C92 = marian-800).
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
                // Aksen 5% dari palet lab: Vivid Magenta (#E01184), dipakai untuk outline kartu aktif
                magenta: '#E01184',
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                '4xl': '2rem',
                '5xl': '2.5rem',
            },
            boxShadow: {
                lembut: '0 10px 30px -12px rgba(38, 60, 146, 0.25)',
            },
        },
    },

    plugins: [forms],
};
