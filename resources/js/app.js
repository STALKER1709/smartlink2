

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

/*
 * Service worker : il ne sert qu'à afficher une page d'attente quand le réseau
 * manque (voir public/sw.js), et il rend l'application installable sur l'écran
 * d'accueil. L'enregistrement est différé après le chargement pour ne pas
 * disputer la bande passante à la page elle-même, et une erreur ici — contexte
 * non sécurisé, navigateur sans prise en charge — ne doit rien casser.
 */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}
