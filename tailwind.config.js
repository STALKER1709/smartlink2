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
            /*
             * Les valeurs vivent dans `resources/css/jetons.css`, pas ici : c'est ce
             * qui permet au schéma sombre de les redéfinir sans qu'une seule vue
             * porte de classe `dark:`.
             *
             * La forme `rgb(var(--x) / <alpha-value>)` n'est pas décorative : sans
             * elle, les modificateurs d'opacité — `bg-primary/20`, employé une
             * soixantaine de fois dans le dépôt — cesseraient de fonctionner.
             */
            colors: {
                surface: 'rgb(var(--surface) / <alpha-value>)',
                'surface-dim': 'rgb(var(--surface-dim) / <alpha-value>)',
                'surface-bright': 'rgb(var(--surface-bright) / <alpha-value>)',
                'surface-container-lowest': 'rgb(var(--surface-container-lowest) / <alpha-value>)',
                'surface-container-low': 'rgb(var(--surface-container-low) / <alpha-value>)',
                'surface-container': 'rgb(var(--surface-container) / <alpha-value>)',
                'surface-container-high': 'rgb(var(--surface-container-high) / <alpha-value>)',
                'surface-container-highest': 'rgb(var(--surface-container-highest) / <alpha-value>)',
                'surface-variant': 'rgb(var(--surface-variant) / <alpha-value>)',
                'on-surface': 'rgb(var(--on-surface) / <alpha-value>)',
                scrim: 'rgb(var(--scrim) / <alpha-value>)',
                'on-surface-variant': 'rgb(var(--on-surface-variant) / <alpha-value>)',
                'inverse-surface': 'rgb(var(--inverse-surface) / <alpha-value>)',
                'inverse-on-surface': 'rgb(var(--inverse-on-surface) / <alpha-value>)',
                outline: 'rgb(var(--outline) / <alpha-value>)',
                'outline-variant': 'rgb(var(--outline-variant) / <alpha-value>)',
                'surface-tint': 'rgb(var(--surface-tint) / <alpha-value>)',
                primary: 'rgb(var(--primary) / <alpha-value>)',
                'on-primary': 'rgb(var(--on-primary) / <alpha-value>)',
                'primary-container': 'rgb(var(--primary-container) / <alpha-value>)',
                'on-primary-container': 'rgb(var(--on-primary-container) / <alpha-value>)',
                'inverse-primary': 'rgb(var(--inverse-primary) / <alpha-value>)',
                secondary: 'rgb(var(--secondary) / <alpha-value>)',
                'on-secondary': 'rgb(var(--on-secondary) / <alpha-value>)',
                'secondary-container': 'rgb(var(--secondary-container) / <alpha-value>)',
                'on-secondary-container': 'rgb(var(--on-secondary-container) / <alpha-value>)',
                tertiary: 'rgb(var(--tertiary) / <alpha-value>)',
                'on-tertiary': 'rgb(var(--on-tertiary) / <alpha-value>)',
                'tertiary-container': 'rgb(var(--tertiary-container) / <alpha-value>)',
                'on-tertiary-container': 'rgb(var(--on-tertiary-container) / <alpha-value>)',
                error: 'rgb(var(--error) / <alpha-value>)',
                'on-error': 'rgb(var(--on-error) / <alpha-value>)',
                'error-container': 'rgb(var(--error-container) / <alpha-value>)',
                'on-error-container': 'rgb(var(--on-error-container) / <alpha-value>)',
                'primary-fixed': 'rgb(var(--primary-fixed) / <alpha-value>)',
                'primary-fixed-dim': 'rgb(var(--primary-fixed-dim) / <alpha-value>)',
                'on-primary-fixed': 'rgb(var(--on-primary-fixed) / <alpha-value>)',
                'on-primary-fixed-variant': 'rgb(var(--on-primary-fixed-variant) / <alpha-value>)',
                'secondary-fixed': 'rgb(var(--secondary-fixed) / <alpha-value>)',
                'secondary-fixed-dim': 'rgb(var(--secondary-fixed-dim) / <alpha-value>)',
                'on-secondary-fixed': 'rgb(var(--on-secondary-fixed) / <alpha-value>)',
                'on-secondary-fixed-variant': 'rgb(var(--on-secondary-fixed-variant) / <alpha-value>)',
                'tertiary-fixed': 'rgb(var(--tertiary-fixed) / <alpha-value>)',
                'tertiary-fixed-dim': 'rgb(var(--tertiary-fixed-dim) / <alpha-value>)',
                'on-tertiary-fixed': 'rgb(var(--on-tertiary-fixed) / <alpha-value>)',
                'on-tertiary-fixed-variant': 'rgb(var(--on-tertiary-fixed-variant) / <alpha-value>)',
                background: 'rgb(var(--background) / <alpha-value>)',
                'on-background': 'rgb(var(--on-background) / <alpha-value>)',
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
                // Le palier qui manquait. Sans lui, la marge de bureau
                // s'appliquait dès 768 px : mesure faite, le pied de page à
                // trois colonnes débordait alors de 38 px.
                'margin-tablet': '32px',
                'margin-desktop': '64px',
            },

            maxWidth: {
                container: '1280px',
            },
        },
    },

    plugins: [forms],
};
