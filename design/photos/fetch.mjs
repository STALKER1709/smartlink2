#!/usr/bin/env node
//
// Va chercher les photographies chez les banques d'images libres, les nomme
// comme `php artisan photos:import` l'attend, et inscrit leur provenance dans
// SOURCES.md au moment du téléchargement.
//
// Pourquoi un script plutôt qu'une archive toute faite : le droit d'usage tient
// au couple fichier + provenance. Une archive anonyme le perd dès le premier
// copier-coller, et personne ne retrouve six mois plus tard d'où venait
// `coiffure-2.jpg`. Ici la ligne de SOURCES.md s'écrit dans le même geste que
// le fichier ; les deux ne peuvent pas se séparer par distraction.
//
//   node design/photos/fetch.mjs --liste
//   node design/photos/fetch.mjs --source openverse --par 2
//   PEXELS_API_KEY=xxx node design/photos/fetch.mjs --source pexels --par 3
//   node design/photos/fetch.mjs --cle coiffure --par 4
//
// Puis :  php artisan photos:import
//
// Node 18+ suffit : aucune dépendance, `fetch` est natif.

import { mkdir, readFile, writeFile, access, readdir } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const ICI = dirname(fileURLToPath(import.meta.url));
const RACINE = join(ICI, '..', '..');

// Les requêtes sont en anglais : c'est la langue d'indexation de toutes ces
// banques, une requête française n'y rend presque rien. Le qualificatif
// africain vient en premier parce que le catalogue par défaut est très
// européen — et qu'un plombier de cuisine berlinoise ne dit rien à un visiteur
// de Douala. Les requêtes suivantes sont des replis : si la première ne rend
// pas assez d'images exploitables, on descend la liste.
const REQUETES = {
    plomberie: ['african plumber working', 'plumber pipe wrench sink', 'plumbing repair hands'],
    coiffure: ['african hair salon braiding', 'black woman hairdresser salon', 'barber shop customer'],
    electricite: ['african electrician wiring', 'electrician electrical panel', 'electrical installation hands'],
    menage: ['african woman cleaning house', 'home cleaning service', 'cleaning floor bucket mop'],
    climatisation: ['air conditioner technician repair', 'hvac technician outdoor unit', 'refrigerator repair technician'],
    traiteur: ['african food cooking pot', 'west african dish plate', 'catering kitchen cooking'],
    menuiserie: ['african carpenter workshop', 'carpenter sawing wood plank', 'woodworking workshop hands'],
    couture: ['african tailor sewing machine', 'african fabric dressmaker', 'seamstress atelier fabric'],
    maconnerie: ['african construction worker bricks', 'mason laying concrete blocks', 'construction site cement'],
    eau: ['water jerrycan africa', 'water containers delivery', 'bottled water delivery'],
    informatique: ['computer repair technician', 'african man repairing phone', 'electronics repair workbench'],
    cours: ['african student studying tutor', 'teacher helping student notebook', 'private tutoring lesson'],
    photo: ['african photographer camera', 'photographer shooting portrait', 'videographer filming'],
    mecanique: ['african mechanic motorcycle', 'car mechanic garage engine', 'motorbike repair street'],
};

const SOURCES = {
    // Licence Pexels : usage commercial, sans attribution. Interdit de revendre
    // la photo telle quelle et de la reverser sur une autre banque d'images —
    // rien de ce que fait SmartLink. Clé gratuite sur pexels.com/api.
    pexels: {
        libelle: 'Pexels',
        licence: 'Licence Pexels (usage commercial, sans attribution)',
        cle: 'PEXELS_API_KEY',
        async chercher(requete, combien) {
            const cle = clefApi('PEXELS_API_KEY');
            const url = new URL('https://api.pexels.com/v1/search');
            url.searchParams.set('query', requete);
            url.searchParams.set('per_page', String(Math.min(80, combien * 4)));
            url.searchParams.set('orientation', 'landscape');

            const reponse = await fetch(url, { headers: { Authorization: cle } });
            if (!reponse.ok) throw new Error(`Pexels a répondu ${reponse.status} — clé API invalide ou quota dépassé.`);

            const donnees = await reponse.json();
            return (donnees.photos ?? []).map((p) => ({
                // Les variantes `src` de Pexels sont l'originale avec des
                // paramètres de redimensionnement : on demande directement le
                // 1200 × 900 que README.md réclame, plutôt que de rapatrier
                // 6 Mo pour les jeter ensuite.
                url: `${p.src.original}?auto=compress&cs=tinysrgb&fit=crop&w=1200&h=900`,
                page: p.url,
                auteur: p.photographer,
                licence: 'Licence Pexels',
                largeur: 1200,
                hauteur: 900,
            }));
        },
    },

    // Openverse est le moteur de la Wikimedia Foundation sur les catalogues
    // sous licence libre. Pas de clé, mais un débit limité par IP. On s'y
    // restreint à CC0 et domaine public : les autres licences Creative Commons
    // exigent une attribution visible, que ce projet n'a nulle part où poser.
    openverse: {
        libelle: 'Openverse',
        licence: 'CC0 / domaine public',
        cle: null,
        async chercher(requete, combien) {
            const url = new URL('https://api.openverse.org/v1/images/');
            url.searchParams.set('q', requete);
            url.searchParams.set('page_size', String(Math.min(20, combien * 4)));
            url.searchParams.set('license', avecAttribution ? '' : 'cc0,pdm');
            if (avecAttribution) url.searchParams.set('license_type', 'commercial');
            url.searchParams.set('aspect_ratio', 'wide');
            url.searchParams.set('mature', 'false');

            const reponse = await fetch(url, { headers: { 'User-Agent': 'SmartLink/1.0 (design/photos/fetch.mjs)' } });
            if (reponse.status === 429) throw new Error('Openverse limite le débit (429). Attendez une minute et relancez.');
            if (!reponse.ok) throw new Error(`Openverse a répondu ${reponse.status}.`);

            const donnees = await reponse.json();
            return (donnees.results ?? [])
                .filter((r) => (r.width ?? 0) >= 1200)
                .map((r) => ({
                    url: r.url,
                    page: r.foreign_landing_url ?? r.url,
                    auteur: r.creator ?? 'inconnu',
                    licence: `${(r.license ?? '?').toUpperCase()} ${r.license_version ?? ''}`.trim(),
                    largeur: r.width,
                    hauteur: r.height,
                }));
        },
    },
};

const args = process.argv.slice(2);
const drapeau = (nom) => args.includes(`--${nom}`);
const valeur = (nom, defaut) => {
    const i = args.indexOf(`--${nom}`);
    return i === -1 ? defaut : args[i + 1];
};

const avecAttribution = drapeau('avec-attribution');
const simuler = drapeau('simuler');
const source = valeur('source', 'openverse');
const parCle = Number(valeur('par', 2));
const cleDemandee = valeur('cle', null);

function clefApi(nom) {
    if (process.env[nom]) return process.env[nom];
    throw new Error(`${nom} n'est pas défini. Créez une clé gratuite puis relancez :\n  ${nom}=xxx node design/photos/fetch.mjs --source ${source}`);
}

async function existe(chemin) {
    try {
        await access(chemin);
        return true;
    } catch {
        return false;
    }
}

async function telecharger(url) {
    const reponse = await fetch(url, { redirect: 'follow' });
    if (!reponse.ok) throw new Error(`téléchargement ${reponse.status}`);

    const type = reponse.headers.get('content-type') ?? '';
    if (!type.startsWith('image/')) throw new Error(`ce n'est pas une image (${type || 'type inconnu'})`);

    const octets = Buffer.from(await reponse.arrayBuffer());
    if (octets.length < 10_000) throw new Error('fichier suspect (moins de 10 Ko)');

    return { octets, extension: type.includes('png') ? 'png' : type.includes('webp') ? 'webp' : 'jpg' };
}

// La provenance s'écrit dans le même passage que le fichier. Le tableau de
// SOURCES.md est en fin de document : on lui ajoute des lignes, sans toucher au
// texte qui l'introduit ni aux lignes déjà présentes.
async function inscrireProvenance(lignes) {
    if (lignes.length === 0) return;

    const chemin = join(ICI, 'SOURCES.md');
    const actuel = await readFile(chemin, 'utf8');
    const mois = new Date().toISOString().slice(0, 7);

    const ajouts = lignes
        .map((l) => `| \`${l.fichier}\` | [${l.source} — ${l.auteur}](${l.page}) | ${l.licence} | ${mois} |`)
        .join('\n');

    await writeFile(chemin, `${actuel.trimEnd()}\n${ajouts}\n`, 'utf8');
}

async function main() {
    if (drapeau('liste')) {
        console.log('Clés reconnues et requêtes employées :\n');
        for (const [cle, requetes] of Object.entries(REQUETES)) {
            console.log(`  ${cle.padEnd(14)} ${requetes.join(' · ')}`);
        }
        console.log('\nSources : ' + Object.entries(SOURCES).map(([n, s]) => `${n} (${s.cle ? s.cle + ' requise' : 'sans clé'})`).join(', '));
        return;
    }

    const banque = SOURCES[source];
    if (!banque) {
        throw new Error(`Source inconnue « ${source} ». Choisissez : ${Object.keys(SOURCES).join(', ')}.`);
    }

    const cles = cleDemandee ? [cleDemandee] : Object.keys(REQUETES);
    for (const cle of cles) {
        if (!REQUETES[cle]) throw new Error(`Clé inconnue « ${cle} ». Voir --liste.`);
    }

    await mkdir(ICI, { recursive: true });
    const deja = new Set(await readdir(ICI));
    const provenances = [];
    let obtenues = 0;

    for (const cle of cles) {
        // Ce qui est déjà là ne se retélécharge pas : le script se relance sans
        // effacer une photo choisie à la main ni dupliquer une ligne de
        // SOURCES.md.
        const presentes = [...deja].filter((f) => f.startsWith(`${cle}-`)).length;
        const manquantes = parCle - presentes;

        if (manquantes <= 0) {
            console.log(`  ${cle.padEnd(14)} déjà ${presentes} photo(s), rien à faire`);
            continue;
        }

        let candidats = [];
        for (const requete of REQUETES[cle]) {
            if (candidats.length >= manquantes * 3) break;
            try {
                candidats = candidats.concat(await banque.chercher(requete, manquantes));
            } catch (e) {
                console.error(`  ${cle.padEnd(14)} ${e.message}`);
                if (/clé API|API_KEY/.test(e.message)) process.exit(1);
            }
        }

        // Deux requêtes voisines rendent souvent la même photo.
        const vus = new Set();
        candidats = candidats.filter((c) => !vus.has(c.page) && vus.add(c.page));

        let rang = presentes;
        for (const candidat of candidats) {
            if (rang >= parCle) break;

            if (simuler) {
                console.log(`  ${cle}-${rang + 1}  ← ${candidat.page} (${candidat.auteur})`);
                rang++;
                obtenues++;
                continue;
            }

            try {
                const { octets, extension } = await telecharger(candidat.url);
                const fichier = `${cle}-${rang + 1}.${extension}`;

                if (await existe(join(ICI, fichier))) {
                    rang++;
                    continue;
                }

                await writeFile(join(ICI, fichier), octets);
                provenances.push({
                    fichier,
                    source: banque.libelle,
                    page: candidat.page,
                    auteur: candidat.auteur,
                    licence: candidat.licence,
                });

                const poids = Math.round(octets.length / 1024);
                const lourd = poids > 500 ? '  ⚠ à recompresser' : '';
                console.log(`  ${fichier.padEnd(20)} ${String(poids).padStart(4)} Ko  ${candidat.largeur}×${candidat.hauteur}${lourd}`);

                rang++;
                obtenues++;
            } catch (e) {
                console.error(`  ${cle.padEnd(14)} ignorée : ${e.message}`);
            }
        }

        if (rang < parCle) {
            console.error(`  ${cle.padEnd(14)} ⚠ ${rang}/${parCle} seulement — affinez REQUETES.${cle} ou changez de source`);
        }
    }

    await inscrireProvenance(provenances);

    console.log(`\n${obtenues} photographie(s) ${simuler ? 'trouvée(s), rien écrit (--simuler)' : 'déposée(s) dans design/photos'}.`);
    if (!simuler && obtenues > 0) {
        console.log('Relisez-les une par une avant d\'importer : une requête rend toujours quelques images hors sujet.');
        console.log('Puis :  php artisan photos:import');
    }
}

main().catch((e) => {
    console.error(`\n${e.message}\n`);
    process.exit(1);
});
