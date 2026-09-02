/*
 * Les cartes, servies par nous.
 *
 * Leaflet venait d'unpkg.com : une requête tierce bloquante sur les deux
 * écrans qui portent une carte, et un `ReferenceError: L is not defined` en
 * pleine page dès que ce CDN n'est pas joignable — ce qui arrive sur une
 * connexion mobile camerounaise bien plus souvent que sur un poste de
 * développement. Le dépôt héberge déjà ses polices pour exactement ces
 * raisons ; la carte suivait la règle inverse.
 *
 * Ce fichier est une entrée Vite à part : il ne part que sur les deux écrans
 * qui en ont besoin, et ne pèse rien sur les 130 autres.
 *
 * Aucun global n'est exposé. Les deux cartes se déclarent par des attributs
 * sur leur conteneur, ce qui retire du même coup les deux scripts en ligne et
 * la dépendance d'ordre entre Alpine et Leaflet : le composant Alpine
 * appelait `L.map()` dans son `init()`, donc avant que le module de Leaflet
 * n'ait pu s'exécuter.
 */

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

/*
 * Les images du marqueur sont référencées par la feuille de Leaflet en chemins
 * relatifs, que le regroupement casse. On les importe pour que Vite les prenne
 * en charge et réécrive leurs URL — sans quoi le marqueur est invisible, sans
 * la moindre erreur.
 */
import marqueur from 'leaflet/dist/images/marker-icon.png';
import marqueur2x from 'leaflet/dist/images/marker-icon-2x.png';
import ombre from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({ iconUrl: marqueur, iconRetinaUrl: marqueur2x, shadowUrl: ombre });

const TUILES = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

function poser(conteneur) {
    const lat = parseFloat(conteneur.dataset.lat);
    const lng = parseFloat(conteneur.dataset.lng);

    if (Number.isNaN(lat) || Number.isNaN(lng)) {
        return;
    }

    const carte = L.map(conteneur, { scrollWheelZoom: false })
        .setView([lat, lng], parseInt(conteneur.dataset.zoom || '13', 10));

    L.tileLayer(TUILES, { attribution: '© OpenStreetMap' }).addTo(carte);

    const modifiable = conteneur.hasAttribute('data-modifiable');
    const repere = L.marker([lat, lng], { draggable: modifiable }).addTo(carte);

    if (conteneur.dataset.libelle) {
        repere.bindPopup(conteneur.dataset.libelle).openPopup();
    }

    if (! modifiable) {
        return;
    }

    const champLat = document.querySelector(conteneur.dataset.champLat);
    const champLng = document.querySelector(conteneur.dataset.champLng);
    const echo = document.querySelector(conteneur.dataset.echo);

    const noter = (latlng) => {
        const y = latlng.lat.toFixed(7);
        const x = latlng.lng.toFixed(7);
        if (champLat) champLat.value = y;
        if (champLng) champLng.value = x;
        if (echo) echo.textContent = `${y}, ${x}`;
    };

    noter(repere.getLatLng());
    repere.on('dragend', (e) => noter(e.target.getLatLng()));
    carte.on('click', (e) => { repere.setLatLng(e.latlng); noter(e.latlng); });
}

document.querySelectorAll('[data-carte]').forEach(poser);
