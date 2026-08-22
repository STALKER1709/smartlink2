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
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                // Vert SmartLink. Construit autour de brand-600 (#0F6F4C) pour
                // les actions principales et brand-700 (#0B5239) pour l'état
                // pressé, de façon à remplacer l'échelle indigo de Breeze sans
                // rien changer d'autre que le nom.
                brand: {
                    50: '#EDF6F1',
                    100: '#E3EFE9',
                    200: '#C2DFD0',
                    300: '#94C7AF',
                    400: '#4FA57F',
                    500: '#1C8259',
                    600: '#0F6F4C',
                    700: '#0B5239',
                    800: '#0A422F',
                    900: '#073626',
                },
            },
        },
    },

    plugins: [forms],
};
