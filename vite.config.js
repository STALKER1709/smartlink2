import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // `carte.js` est une entrée à part : Leaflet pèse une centaine de
            // kilo-octets et ne sert qu'aux deux écrans qui portent une carte.
            // Le mettre dans app.js le ferait payer aux 132 autres.
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/carte.js'],
            refresh: true,
        }),
    ],
});
