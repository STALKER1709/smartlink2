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
                surface: '#f9f9fc',
                'surface-dim': '#dadadc',
                'surface-bright': '#f9f9fc',
                'surface-container-lowest': '#ffffff',
                'surface-container-low': '#f3f3f6',
                'surface-container': '#eeeef0',
                'surface-container-high': '#e8e8ea',
                'surface-container-highest': '#e2e2e5',
                'surface-variant': '#e2e2e5',
                'on-surface': '#1a1c1e',
                'on-surface-variant': '#3e4944',
                'inverse-surface': '#2f3133',
                'inverse-on-surface': '#f0f0f3',
                outline: '#6e7a74',
                'outline-variant': '#bdc9c2',
                'surface-tint': '#006c53',
                primary: '#005f48',
                'on-primary': '#ffffff',
                'primary-container': '#007a5e',
                'on-primary-container': '#a4ffdd',
                'inverse-primary': '#79d8b7',
                secondary: '#805600',
                'on-secondary': '#ffffff',
                'secondary-container': '#feb637',
                'on-secondary-container': '#6e4900',
                tertiary: '#aa001a',
                'on-tertiary': '#ffffff',
                'tertiary-container': '#d41829',
                'on-tertiary-container': '#ffe8e6',
                error: '#ba1a1a',
                'on-error': '#ffffff',
                'error-container': '#ffdad6',
                'on-error-container': '#93000a',
                'primary-fixed': '#95f5d2',
                'primary-fixed-dim': '#79d8b7',
                'on-primary-fixed': '#002117',
                'on-primary-fixed-variant': '#00513d',
                'secondary-fixed': '#ffddaf',
                'secondary-fixed-dim': '#ffba43',
                'on-secondary-fixed': '#281800',
                'on-secondary-fixed-variant': '#614000',
                'tertiary-fixed': '#ffdad7',
                'tertiary-fixed-dim': '#ffb3ae',
                'on-tertiary-fixed': '#410004',
                'on-tertiary-fixed-variant': '#930015',
                background: '#f9f9fc',
                'on-background': '#1a1c1e',
            },

            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                'display-lg': ['Lexend', ...defaultTheme.fontFamily.sans],
                'headline-lg': ['Lexend', ...defaultTheme.fontFamily.sans],
                'headline-md': ['Lexend', ...defaultTheme.fontFamily.sans],
                'headline-sm': ['Lexend', ...defaultTheme.fontFamily.sans],
                'body-lg': ['Inter', ...defaultTheme.fontFamily.sans],
                'body-md': ['Inter', ...defaultTheme.fontFamily.sans],
                'label-md': ['Inter', ...defaultTheme.fontFamily.sans],
                'label-sm': ['Inter', ...defaultTheme.fontFamily.sans],
                'button-text': ['Inter', ...defaultTheme.fontFamily.sans],
                // La chasse fixe n'a pas d'équivalent dans la charte amont, et
                // le rôle qu'elle remplit ici n'est pas décoratif : aligner les
                // colonnes de montants, distinguer une donnée d'une phrase.
                // Elle traverse la bascule.
                'label-numeric': ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },

            fontSize: {
                'display-lg': ['48px', { lineHeight: '56px', letterSpacing: '-0.02em', fontWeight: '700' }],
                'headline-lg': ['32px', { lineHeight: '40px', fontWeight: '600' }],
                'headline-md': ['24px', { lineHeight: '32px', fontWeight: '600' }],
                // Le titre d'une carte ou d'une rangée. La charte amont ne le
                // nomme pas — et la maquette de son accueil est allée chercher
                // `text-[20px]` six fois de suite, faute de l'avoir. Il est
                // nommé ici.
                'headline-sm': ['20px', { lineHeight: '28px', fontWeight: '600' }],
                'body-lg': ['18px', { lineHeight: '28px', fontWeight: '400' }],
                'body-md': ['16px', { lineHeight: '24px', fontWeight: '400' }],
                'label-md': ['14px', { lineHeight: '20px', letterSpacing: '0.01em', fontWeight: '600' }],
                'label-sm': ['12px', { lineHeight: '16px', fontWeight: '500' }],
                'label-numeric': ['14px', { lineHeight: '20px', fontWeight: '500' }],
                'button-text': ['16px', { lineHeight: '24px', fontWeight: '600' }],
            },

            /*
             * La profondeur, désormais tonale et ombrée. Trois paliers, et
             * pas un de plus : une ombre écrite à la main est une valeur que
             * personne n'a décidée, et `CharteTest` la refuse.
             *
             * Le survol passe de `elevation-1` à `elevation-2` — le
             * soulèvement se lit dans l'ombre, jamais dans une translation :
             * un bloc qui se déplace emmène avec lui ce que l'œil suivait.
             */
            boxShadow: {
                'elevation-1': '0 4px 12px rgba(26, 28, 30, 0.12)',
                'elevation-2': '0 8px 24px rgba(26, 28, 30, 0.16)',
                overlay: '0 12px 32px rgba(26, 28, 30, 0.20)',
            },

            borderRadius: {
                sm: '0.125rem',
                DEFAULT: '0.25rem',
                md: '0.375rem',
                lg: '0.5rem',
                xl: '0.75rem',
            },

            spacing: {
                unit: '8px',
                gutter: '24px',
                // La charte amont pose 64 px de marge — mais elle ne décrit
                // que le bureau, et sa maquette d'accueil applique ces 64 px
                // sans le moindre palier : sur un 390 px, 128 px de marges ne
                // laissent que 262 px de contenu. Le téléphone garde ses 16 px.
                'margin-mobile': '16px',
                'margin-desktop': '64px',
            },

            maxWidth: {
                container: '1280px',
            },
        },
    },

    plugins: [forms],
};
