/*
 * Service worker de SmartLink — délibérément minimal.
 *
 * Il ne fait qu'une chose : quand une navigation échoue faute de réseau,
 * afficher une page d'attente plutôt que l'écran d'erreur du navigateur. Sur
 * les connexions mobiles du pays, c'est la panne la plus courante.
 *
 * Ce qu'il ne fait pas, et ne doit pas faire : mettre en cache les pages du
 * site. Une page HTML servie depuis un cache affiche des données périmées —
 * une demande déjà acceptée, un abonnement déjà réglé — sans que rien ne
 * l'indique, et le visiteur ne peut rien y faire. Le réseau reste donc seul
 * maître des réponses ; le cache ne contient que la page de repli.
 *
 * Pour retirer ce fichier du parc un jour : remplacer son contenu par un
 * `self.registration.unregister()` dans l'événement « activate » et le
 * déployer. Un service worker supprimé du serveur reste sinon installé chez
 * ceux qui l'ont déjà.
 */
const CACHE = 'smartlink-hors-ligne-v1';
const PAGE_HORS_LIGNE = '/hors-ligne';

self.addEventListener('install', (evenement) => {
    evenement.waitUntil(
        caches
            .open(CACHE)
            .then((cache) => cache.addAll([PAGE_HORS_LIGNE, '/images/icone-192.png']))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (evenement) => {
    evenement.waitUntil(
        caches
            .keys()
            .then((noms) => Promise.all(noms.filter((nom) => nom !== CACHE).map((nom) => caches.delete(nom))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (evenement) => {
    const requete = evenement.request;

    // Seules les navigations sont interceptées : une image ou une feuille de
    // style manquante dégrade la page, elle ne la remplace pas.
    if (requete.method !== 'GET' || requete.mode !== 'navigate') {
        return;
    }

    evenement.respondWith(fetch(requete).catch(() => caches.match(PAGE_HORS_LIGNE)));
});
