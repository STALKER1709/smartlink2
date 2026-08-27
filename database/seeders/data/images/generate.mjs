/*
 * Génère les illustrations de couverture des services de démonstration.
 *
 * Pourquoi des dessins et non des photographies : une photo de stock d'un
 * plombier européen dans une cuisine européenne dessert une place de marché
 * camerounaise, et une photo trouvée en ligne pose une question de droits que
 * personne ne veut découvrir après la mise en ligne. Ces images-ci sont
 * produites ici, à partir de la palette de la plateforme : aucun tiers, aucune
 * licence, et un rendu qui a l'air voulu plutôt que bouché.
 *
 * Chaque motif est désormais posé dans une **scène** : un ciel avec sa lumière
 * basse, une ligne d'horizon, un sol qui reçoit une ombre de contact, des
 * feuillages au premier plan et une silhouette au travail. Seuls, les motifs
 * flottaient sur un dégradé et se lisaient comme des pictogrammes de
 * remplacement — ce qu'ils étaient. La scène ne demande pas de redessiner les
 * quatorze métiers : elle les entoure.
 *
 * Pas d'ombre de contact : les motifs ne posent pas tous au sol — un tuyau
 * et ses gouttes se lisent en l'air, une tête de coiffure se lit assise — et
 * une ombre sous un objet qui flotte est pire que pas d'ombre du tout.
 *
 * Le jour où de vraies photographies arrivent, elles les remplacent sans
 * toucher au code : `php artisan photos:import` les prend dans
 * `design/photos/` et les pose au bon endroit.
 *
 *   node database/seeders/data/images/generate.mjs
 *
 * Le rendu passe par Chromium plutôt que par une bibliothèque SVG : c'est le
 * même moteur que celui des visiteurs, donc ce qui sort du script est ce qu'ils
 * verront.
 */
import { chromium } from 'playwright';
import { mkdir, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const ICI = dirname(fileURLToPath(import.meta.url));
const L = 800, H = 600;

/* La ligne d'horizon, partagée par la scène et par la pose des motifs : c'est
   sur elle qu'ils s'assoient. */
const SOL = 402;

/* Trois déclinaisons de la palette : deux services d'un même métier ne
   partagent pas exactement la même image. */
const VARIANTES = [
    { haut: '#e8f5ee', bas: '#cfe9dd', trait: '#005538', clair: '#aff1cf', accent: '#a04700' },
    { haut: '#f1f8f4', bas: '#c6e5d6', trait: '#0f6f4c', clair: '#9ff4c8', accent: '#7b3500' },
    { haut: '#e2f0e9', bas: '#bfe0d1', trait: '#00432c', clair: '#93d4b3', accent: '#c4682a' },
];

/* La variante change la taille et la position du motif dans la scène : sans
   cela, deux services d'un même métier donnaient deux images que rien ne
   distinguait à l'œil sur la grille, ce qui a l'air d'un bogue d'affichage
   plutôt que d'un choix.
   
   Ces valeurs entrent dans la *pose*, calculée après mesure. Appliquées
   par-dessus comme un cadrage global, elles décollaient le motif du sol qu'on
   venait de lui donner. */
const CADRAGES = [
    { hauteur: 250, decalage: 0 },
    { hauteur: 218, decalage: -74 },
    { hauteur: 272, decalage: 58 },
];

/* Chaque motif reçoit la variante et rend le corps du dessin, centré autour
   de (400, 300). Les formes restent plates et peu nombreuses : à la taille
   d'une vignette, le détail se perd et le contraste seul se lit. */
const MOTIFS = {
    plomberie: (v) => `
        <rect x="196" y="262" width="330" height="56" rx="10" fill="${v.trait}"/>
        <rect x="180" y="246" width="42" height="88" rx="10" fill="${v.accent}"/>
        <rect x="470" y="246" width="42" height="88" rx="10" fill="${v.accent}"/>
        <rect x="512" y="262" width="56" height="56" rx="10" fill="${v.trait}"/>
        <path d="M540 318 q34 60 34 88 a34 34 0 0 1-68 0 q0-28 34-88z" fill="${v.clair}"/>
        <g transform="rotate(-38 620 210)">
            <rect x="600" y="180" width="40" height="200" rx="18" fill="${v.trait}"/>
            <path d="M596 190 v-58 h16 v30 h32 v-30 h16 v58 a32 32 0 0 1-64 0z" fill="${v.trait}"/>
        </g>
        <path d="M300 372 q20 34 20 48 a20 20 0 0 1-40 0 q0-14 20-48z" fill="${v.clair}" opacity=".65"/>
        <path d="M380 400 q14 24 14 34 a14 14 0 0 1-28 0 q0-10 14-34z" fill="${v.clair}" opacity=".45"/>`,

    coiffure: (v) => `
        <path d="M400 130 c-92 0-150 62-150 148 0 96 40 150 40 194 h220 c0-44 40-98 40-194 0-86-58-148-150-148z" fill="${v.trait}"/>
        <circle cx="400" cy="300" r="86" fill="${v.accent}" opacity=".28"/>
        ${[0, 1, 2, 3, 4].map((i) => `<path d="M${300 + i * 50} 250 q10 90 0 220" stroke="${v.clair}" stroke-width="9" fill="none" stroke-linecap="round" opacity=".85"/>`).join('')}
        <rect x="556" y="196" width="26" height="150" rx="10" fill="${v.accent}"/>
        ${[0, 1, 2, 3, 4, 5].map((i) => `<rect x="582" y="${206 + i * 24}" width="46" height="10" rx="5" fill="${v.accent}"/>`).join('')}`,

    electricite: (v) => `
        <path d="M400 120 c-74 0-134 58-134 130 0 52 30 82 46 108 10 17 14 30 14 44h148c0-14 4-27 14-44 16-26 46-56 46-108 0-72-60-130-134-130z" fill="${v.clair}"/>
        <rect x="336" y="418" width="128" height="26" rx="10" fill="${v.trait}"/>
        <rect x="352" y="452" width="96" height="22" rx="10" fill="${v.trait}"/>
        <rect x="368" y="482" width="64" height="20" rx="10" fill="${v.trait}" opacity=".6"/>
        <path d="M418 178 l-66 118h50l-18 92 74-124h-52z" fill="${v.accent}"/>
        ${[[196, 200], [604, 200], [180, 320], [620, 320]].map(([x, y]) => `<circle cx="${x}" cy="${y}" r="12" fill="${v.trait}" opacity=".25"/>`).join('')}`,

    menage: (v) => `
        <g transform="rotate(18 470 140)">
            <rect x="454" y="120" width="30" height="230" rx="14" fill="${v.accent}"/>
            <path d="M418 344 h102 l26 44 h-154z" fill="${v.trait}"/>
            <path d="M392 388 h154 l-14 78 h-126z" fill="${v.trait}" opacity=".8"/>
            ${[0, 1, 2, 3, 4, 5].map((i) => `<rect x="${402 + i * 25}" y="392" width="9" height="72" rx="4" fill="${v.haut}" opacity=".55"/>`).join('')}
        </g>
        <path d="M186 316 h198 l-22 190 a24 24 0 0 1-24 21 h-106 a24 24 0 0 1-24-21z" fill="${v.trait}"/>
        <rect x="168" y="286" width="234" height="42" rx="16" fill="${v.accent}"/>
        <path d="M212 328 h50 l-13 205 h-26z" fill="${v.clair}" opacity=".35"/>
        ${[[236, 202, 34], [300, 152, 21], [166, 172, 15]].map(([x, y, r]) => `<circle cx="${x}" cy="${y}" r="${r}" fill="none" stroke="${v.trait}" stroke-width="7" opacity=".5"/>`).join('')}`,

    climatisation: (v) => `
        <rect x="212" y="150" width="376" height="130" rx="26" fill="${v.trait}"/>
        <rect x="240" y="182" width="200" height="20" rx="10" fill="${v.clair}" opacity=".55"/>
        <rect x="212" y="252" width="376" height="28" rx="14" fill="${v.accent}"/>
        ${[0, 1, 2].map((i) => `
            <path d="M${268 + i * 132} 322 q34 34 0 68 q-34 34 0 68" stroke="${v.trait}" stroke-width="14"
                  fill="none" stroke-linecap="round" opacity="${0.85 - i * 0.2}"/>`).join('')}`,

    traiteur: (v) => `
        <path d="M242 306 h316 l-30 194 a30 30 0 0 1-30 25 h-196 a30 30 0 0 1-30-25z" fill="${v.trait}"/>
        <rect x="222" y="272" width="356" height="42" rx="18" fill="${v.accent}"/>
        <rect x="368" y="240" width="64" height="34" rx="14" fill="${v.accent}"/>
        ${[0, 1, 2].map((i) => `
            <path d="M${330 + i * 70} 216 q30-34 0-68 q-30-34 0-68" stroke="${v.clair}" stroke-width="13"
                  fill="none" stroke-linecap="round" opacity="${0.9 - i * 0.18}"/>`).join('')}`,

    menuiserie: (v) => `
        <rect x="150" y="356" width="500" height="76" rx="10" fill="${v.accent}" opacity=".85"/>
        <rect x="150" y="432" width="500" height="26" rx="8" fill="${v.accent}" opacity=".45"/>
        <g transform="rotate(-14 210 300)">
            <path d="M210 244 h330 v52 ${[...Array(11)].map((_, i) => `l-15 22 l-15-22`).join(' ')} z" fill="${v.trait}"/>
            <rect x="126" y="228" width="96" height="46" rx="20" fill="${v.trait}"/>
            <rect x="146" y="244" width="56" height="16" rx="8" fill="${v.haut}" opacity=".5"/>
        </g>
        ${[0, 1, 2, 3, 4].map((i) => `
            <path d="M${248 + i * 76} 494 q14-20 28 0 q14 20 28 0" stroke="${v.trait}" stroke-width="7"
                  fill="none" stroke-linecap="round" opacity=".4"/>`).join('')}`,

    couture: (v) => `
        <rect x="176" y="418" width="448" height="52" rx="16" fill="${v.trait}"/>
        <rect x="196" y="188" width="150" height="152" rx="30" fill="${v.trait}"/>
        <rect x="196" y="330" width="86" height="92" rx="20" fill="${v.trait}"/>
        <rect x="330" y="196" width="252" height="46" rx="22" fill="${v.trait}"/>
        <rect x="522" y="240" width="52" height="106" rx="18" fill="${v.trait}"/>
        <rect x="536" y="346" width="24" height="30" rx="8" fill="${v.accent}"/>
        <path d="M548 376 v40" stroke="${v.accent}" stroke-width="7" stroke-linecap="round"/>
        <circle cx="271" cy="264" r="34" fill="${v.clair}" opacity=".7"/>
        <rect x="356" y="150" width="26" height="52" rx="8" fill="${v.accent}"/>
        <path d="M369 202 q-40 90 60 174 t120 40" stroke="${v.accent}" stroke-width="7" fill="none" stroke-linecap="round" opacity=".75"/>`,

    maconnerie: (v) => `
        ${[0, 1, 2, 3].map((r) => `
            ${[0, 1, 2, 3].map((c) => `
                <rect x="${196 + c * 106 + (r % 2 ? 53 : 0)}" y="${268 + r * 64}" width="94" height="52" rx="8"
                      fill="${v.trait}" opacity="${0.9 - r * 0.13}"/>`).join('')}`).join('')}
        <path d="M470 130 l120 62-92 74z" fill="${v.accent}"/>
        <rect x="576" y="140" width="70" height="20" rx="10" transform="rotate(28 576 140)" fill="${v.accent}"/>`,

    eau: (v) => `
        <rect x="272" y="216" width="256" height="290" rx="34" fill="${v.trait}"/>
        <rect x="356" y="164" width="88" height="60" rx="18" fill="${v.accent}"/>
        <rect x="306" y="286" width="188" height="180" rx="20" fill="${v.clair}" opacity=".6"/>
        <path d="M400 306 q54 84 54 124 a54 54 0 0 1-108 0 q0-40 54-124z" fill="${v.haut}" opacity=".9"/>
        <path d="M198 214 q22 36 22 54 a22 22 0 0 1-44 0 q0-18 22-54z" fill="${v.trait}" opacity=".45"/>
        <path d="M604 260 q18 30 18 44 a18 18 0 0 1-36 0 q0-14 18-44z" fill="${v.trait}" opacity=".35"/>`,

    informatique: (v) => `
        <rect x="230" y="180" width="340" height="222" rx="20" fill="${v.trait}"/>
        <rect x="254" y="206" width="292" height="170" rx="10" fill="${v.clair}" opacity=".55"/>
        <path d="M186 402 h428 l24 44 a14 14 0 0 1-13 21 h-450 a14 14 0 0 1-13-21z" fill="${v.trait}"/>
        <rect x="340" y="424" width="120" height="14" rx="7" fill="${v.clair}" opacity=".7"/>
        <circle cx="400" cy="290" r="46" fill="none" stroke="${v.accent}" stroke-width="18"/>
        ${[0, 1, 2, 3, 4, 5].map((i) => {
            const a = (i * Math.PI) / 3;
            return `<rect x="${(392 + Math.cos(a) * 62).toFixed(1)}" y="${(282 + Math.sin(a) * 62).toFixed(1)}"
                          width="17" height="17" rx="4" fill="${v.accent}"/>`;
        }).join('')}`,

    cours: (v) => `
        <rect x="176" y="150" width="448" height="288" rx="20" fill="${v.trait}"/>
        <rect x="200" y="174" width="400" height="240" rx="10" fill="${v.haut}" opacity=".14"/>
        <path d="M244 240 h120 M244 288 h180 M244 336 h96" stroke="${v.clair}" stroke-width="13" stroke-linecap="round" opacity=".85"/>
        <path d="M470 250 l40 60-40 60" stroke="${v.accent}" stroke-width="14" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
        <rect x="176" y="438" width="448" height="24" rx="10" fill="${v.accent}"/>
        <path d="M300 462 l-30 76 M500 462 l30 76" stroke="${v.trait}" stroke-width="16" stroke-linecap="round"/>`,

    photo: (v) => `
        <path d="M206 250 h108 l34-44 h104 l34 44 h108 a26 26 0 0 1 26 26 v186 a26 26 0 0 1-26 26 h-388 a26 26 0 0 1-26-26 v-186 a26 26 0 0 1 26-26z" fill="${v.trait}"/>
        <circle cx="400" cy="368" r="88" fill="${v.clair}" opacity=".5"/>
        <circle cx="400" cy="368" r="52" fill="none" stroke="${v.accent}" stroke-width="18"/>
        <circle cx="540" cy="292" r="14" fill="${v.accent}"/>`,

    mecanique: (v) => `
        <path d="M180 380 l44-96 a34 34 0 0 1 31-20 h290 a34 34 0 0 1 31 20 l44 96z" fill="${v.trait}"/>
        <path d="M266 296 h268 l26 62 h-320z" fill="${v.clair}" opacity=".55"/>
        <rect x="168" y="380" width="464" height="70" rx="22" fill="${v.trait}"/>
        <circle cx="262" cy="452" r="46" fill="${v.accent}"/>
        <circle cx="538" cy="452" r="46" fill="${v.accent}"/>
        <circle cx="262" cy="452" r="18" fill="${v.haut}"/>
        <circle cx="538" cy="452" r="18" fill="${v.haut}"/>
        <path d="M560 168 a40 40 0 1 0 46 46 l52 52 -30 30 -52-52 a40 40 0 0 0-16-76z" fill="${v.accent}" opacity=".8"/>`,
};

/* Quel dessin pour quelle catégorie. Une catégorie sans entrée retombe sur un
   motif neutre plutôt que sur une image vide. */
const PAR_CATEGORIE = {
    Plomberie: 'plomberie',
    Coiffure: 'coiffure',
    'Électricité': 'electricite',
    'Ménage': 'menage',
    'Climatisation & réfrigération': 'climatisation',
    'Traiteur & cuisine': 'traiteur',
    Menuiserie: 'menuiserie',
    'Couture & stylisme': 'couture',
    'Maçonnerie': 'maconnerie',
    "Livraison d'eau": 'eau',
    'Informatique & réparation': 'informatique',
    'Cours particuliers': 'cours',
    'Photographie & vidéo': 'photo',
    'Mécanique auto & moto': 'mecanique',
};

/* La silhouette d'une personne au travail : ce qui distingue un décor d'un
   lieu où quelqu'un exerce. Volontairement petite et sans visage — elle situe
   une échelle, elle ne représente personne. */
function silhouette(v, x, y, echelle) {
    return `<g transform="translate(${x} ${y}) scale(${echelle})" fill="${v.trait}" opacity=".5">
        <circle cx="0" cy="-64" r="15"/>
        <path d="M-15 -46 q15-10 30 0 l7 52 -12 4 -6-34 -5 46 -8 0 -5-46 -6 34 -12-4z"/>
    </g>`;
}

/* Le décor : ciel, lumière basse, horizon, sol. Les proportions ne changent
   pas d'une variante à l'autre — c'est la lumière et le cadrage qui varient,
   comme deux photos d'un même lieu à deux heures différentes. */
function scene(v, variante) {
    const horizon = SOL;
    const gauche = variante % 2 === 0;
    const soleilX = gauche ? 168 : 636;

    return `
        <rect width="${L}" height="${H}" fill="url(#ciel)"/>

        <circle cx="${soleilX}" cy="${horizon - 96}" r="230" fill="url(#lumiere)"/>
        <circle cx="${soleilX}" cy="${horizon - 96}" r="52" fill="${v.clair}" opacity=".55"/>

                <path d="M0 ${horizon - 54} q120-46 232-8 t208 4 q120-40 240 6 t120 12 V${H} H0z" fill="${v.trait}" opacity=".10"/>
        <path d="M0 ${horizon - 18} q160-38 300 2 t260-6 q140-24 240 14 V${H} H0z" fill="${v.trait}" opacity=".16"/>

        <rect y="${horizon}" width="${L}" height="${H - horizon}" fill="url(#sol)"/>
        <ellipse cx="${soleilX}" cy="${horizon + 24}" rx="300" ry="54" fill="${v.clair}" opacity=".22"/>`;
}

/* Le premier plan : feuillages aux deux coins bas. Ils ferment le cadre et
   donnent une échelle au motif, qui sans eux paraissait posé sur rien. */
function feuillage(v) {
    const feuille = (x, y, rot, taille, opacite) => `
        <g transform="translate(${x} ${y}) rotate(${rot}) scale(${taille})" opacity="${opacite}">
            <path d="M0 0 q46-72 108-84 q-8 74-64 104 q-30 16-44-20z" fill="${v.trait}"/>
            <path d="M0 0 q52-56 104-80" stroke="${v.bas}" stroke-width="3" fill="none" opacity=".5"/>
        </g>`;

    // Les deux feuilles de droite poussent vers la gauche : un miroir, et non
    // une rotation de 180° qui les envoyait hors du cadre.
    return `
        ${feuille(-14, H + 16, -24, 1.3, '.6')}
        ${feuille(104, H + 52, -58, 0.95, '.45')}
        <g transform="translate(${L} 0) scale(-1 1)">
            ${feuille(-14, H + 12, -20, 1.35, '.55')}
            ${feuille(112, H + 50, -56, 0.9, '.4')}
        </g>`;
}

function svg(motif, variante, pose) {
    const v = VARIANTES[variante];
    const corps = (MOTIFS[motif] ?? MOTIFS.informatique)(v);
    const gauche = variante % 2 === 0;

    return `<svg xmlns="http://www.w3.org/2000/svg" width="${L}" height="${H}" viewBox="0 0 ${L} ${H}">
        <defs>
            <linearGradient id="ciel" x1="0" y1="0" x2="0.25" y2="1">
                <stop offset="0" stop-color="${v.haut}"/>
                <stop offset="0.62" stop-color="${v.bas}"/>
                <stop offset="1" stop-color="${v.clair}" stop-opacity=".8"/>
            </linearGradient>
            <linearGradient id="sol" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="${v.trait}" stop-opacity=".18"/>
                <stop offset="1" stop-color="${v.trait}" stop-opacity=".42"/>
            </linearGradient>
            <radialGradient id="lumiere">
                <stop offset="0" stop-color="${v.clair}" stop-opacity=".85"/>
                <stop offset="1" stop-color="${v.clair}" stop-opacity="0"/>
            </radialGradient>
            <radialGradient id="vignette" cx="0.5" cy="0.46" r="0.78">
                <stop offset="0.55" stop-color="#000" stop-opacity="0"/>
                <stop offset="1" stop-color="${v.trait}" stop-opacity=".18"/>
            </radialGradient>
        </defs>

        ${scene(v, variante)}

        ${silhouette(v, gauche ? 688 : 116, 446, 1.05)}

                <g transform="${pose.transform}">${corps}</g>

        ${feuillage(v)}
        <rect width="${L}" height="${H}" fill="url(#vignette)"/>
    </svg>`;
}

const cible = join(ICI);
await mkdir(cible, { recursive: true });

const navigateur = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
const page = await navigateur.newPage({ viewport: { width: L, height: H }, deviceScaleFactor: 1 });

let rendus = 0;

/* Chaque motif a ses propres proportions : les poser tous avec la même
   translation en laissait flotter la moitié dans le ciel et enterrait l'autre.
   On mesure donc la boîte englobante réelle dans le navigateur, puis on calcule
   l'échelle et le décalage qui posent le motif sur l'horizon, centré.
   Deviner une constante marchait pour deux motifs sur quatorze. */
async function mesurer(motif, variante) {
    const v = VARIANTES[variante];
    const cadrage = CADRAGES[variante];
    await page.setContent(
        `<svg xmlns="http://www.w3.org/2000/svg" width="${L}" height="${H}">` +
        `<g id="m">${(MOTIFS[motif] ?? MOTIFS.informatique)(v)}</g></svg>`,
    );

    const boite = await page.evaluate(() => {
        const b = document.getElementById('m').getBBox();
        return { x: b.x, y: b.y, w: b.width, h: b.height };
    });

    const echelle = Math.min(cadrage.hauteur / boite.h, 360 / boite.w);
    const cx = boite.x + boite.w / 2;
    const bas = boite.y + boite.h;
    const centre = 400 + cadrage.decalage;

    return {
        centre,
        transform: `translate(${centre - cx * echelle} ${SOL - 6 - bas * echelle}) scale(${echelle})`,
        rayon: Math.round((boite.w * echelle) / 2 + 24),
    };
}

for (const motif of Object.keys(MOTIFS)) {
    for (let variante = 0; variante < VARIANTES.length; variante++) {
        const pose = await mesurer(motif, variante);
        const markup = svg(motif, variante, pose);
        await page.setContent(
            `<style>html,body{margin:0;padding:0;overflow:hidden}svg{display:block}</style>${markup}`,
        );
        // JPEG plutôt que PNG : ces dessins sont des aplats sur dégradé, que
        // le PNG encode à 120 Ko pièce là où le JPEG en demande 25 sans
        // différence visible à la taille d'une vignette.
        const image = await page.screenshot({ type: 'jpeg', quality: 86 });
        await writeFile(join(cible, `${motif}-${variante}.jpg`), image);
        rendus++;
    }
}

await navigateur.close();
await writeFile(
    join(cible, 'categories.json'),
    JSON.stringify(PAR_CATEGORIE, null, 4) + '\n',
);

console.log(`${rendus} illustrations rendues dans ${cible}`);
