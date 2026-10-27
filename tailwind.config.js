import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                coffee: '#3B2314',     // Coffee Brown 
                amber: {
                    DEFAULT: '#D97706', // Golden Amber → bg-amber
                    dark: '#B45309',    // → bg-amber-dark
                    light: '#F5B455',   // → text-amber-light
                },
                cream: '#FDFBF7',      // Soft Cream
                charcoal: '#1F2937',   // Charcoal
            },
            fontFamily: {
                display: ['"DM Serif Display"', 'serif'],
                sans: ['"DM Sans"', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};