import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Design tokens SmartLink — voir DESIGN.md à la racine, qui fait foi. Ne pas
 * modifier ces valeurs sans l'y répercuter : c'est lui que lisent les écrans
 * suivants, pas ce fichier.
 */

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            /*
             * Palier « xs » : en dessous, une grille à deux colonnes coupe les
             * titres des services et renvoie le prix à la ligne. Le premier
             * palier de Tailwind est à 640 px, ce qui laisse tous les
             * téléphones du mauvais côté — et c'est là que se passe l'essentiel
             * du trafic au Cameroun.
             */
            screens: {
                xs: '480px',
            },
            colors: {
                surface: '#f9faf7',
                'surface-dim': '#d9dad7',
                'surface-bright': '#f9faf7',
                'surface-container-lowest': '#ffffff',
                'surface-container-low': '#f3f4f1',
                'surface-container': '#edeeeb',
                'surface-container-high': '#e7e8e6',
                'surface-container-highest': '#e2e3e0',
                'surface-variant': '#e2e3e0',
                'on-surface': '#191c1b',
                'on-surface-variant': '#3f4943',
                'inverse-surface': '#2e312f',
                'inverse-on-surface': '#f0f1ee',
                outline: '#6f7a72',
                'outline-variant': '#bec9c0',
                'surface-tint': '#086c49',
                primary: '#005538',
                'on-primary': '#ffffff',
                'primary-container': '#0f6f4c',
                'on-primary-container': '#9aefc3',
                'inverse-primary': '#83d7ad',
                secondary: '#2a694f',
                'on-secondary': '#ffffff',
                'secondary-container': '#aff1cf',
                'on-secondary-container': '#317054',
                tertiary: '#7b3500',
                'on-tertiary': '#ffffff',
                'tertiary-container': '#a04700',
                'on-tertiary-container': '#ffd4bf',
                error: '#ba1a1a',
                'on-error': '#ffffff',
                'error-container': '#ffdad6',
                'on-error-container': '#93000a',
                'primary-fixed': '#9ff4c8',
                'primary-fixed-dim': '#83d7ad',
                'on-primary-fixed': '#002113',
                'on-primary-fixed-variant': '#005236',
                'secondary-fixed': '#aff1cf',
                'secondary-fixed-dim': '#93d4b3',
                'on-secondary-fixed': '#002114',
                'on-secondary-fixed-variant': '#095138',
                'tertiary-fixed': '#ffdbca',
                'tertiary-fixed-dim': '#ffb68e',
                'on-tertiary-fixed': '#331200',
                'on-tertiary-fixed-variant': '#763300',
                background: '#f9faf7',
                'on-background': '#191c1b',
            },

            fontFamily: {
                sans: ['Source Sans 3', ...defaultTheme.fontFamily.sans],
                'headline-xl': ['Hanken Grotesk', ...defaultTheme.fontFamily.sans],
                'headline-lg': ['Hanken Grotesk', ...defaultTheme.fontFamily.sans],
                'headline-md': ['Hanken Grotesk', ...defaultTheme.fontFamily.sans],
                'body-lg': ['Source Sans 3', ...defaultTheme.fontFamily.sans],
                'body-md': ['Source Sans 3', ...defaultTheme.fontFamily.sans],
                'button-text': ['Source Sans 3', ...defaultTheme.fontFamily.sans],
                'label-numeric': ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },

            fontSize: {
                'headline-xl': ['36px', { lineHeight: '44px', letterSpacing: '-0.02em', fontWeight: '800' }],
                'headline-lg': ['28px', { lineHeight: '34px', letterSpacing: '-0.01em', fontWeight: '700' }],
                'headline-md': ['22px', { lineHeight: '28px', letterSpacing: '-0.01em', fontWeight: '600' }],
                'body-lg': ['18px', { lineHeight: '26px', fontWeight: '400' }],
                'body-md': ['16px', { lineHeight: '24px', fontWeight: '400' }],
                'label-numeric': ['14px', { lineHeight: '20px', fontWeight: '500' }],
                'button-text': ['16px', { lineHeight: '24px', fontWeight: '600' }],
            },

            borderRadius: {
                sm: '0.125rem',
                DEFAULT: '0.25rem',
                md: '0.375rem',
                lg: '0.5rem',
                xl: '0.75rem',
            },

            spacing: {
                unit: '4px',
                gutter: '16px',
                'margin-mobile': '16px',
                'margin-desktop': '32px',
            },

            maxWidth: {
                container: '1200px',
            },
        },
    },

    plugins: [forms],
};
