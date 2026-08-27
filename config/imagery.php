<?php

/*
|--------------------------------------------------------------------------
| Photographies
|--------------------------------------------------------------------------
|
| Une photographie par métier, employée sur la vignette de chaque service et
| sur les cartes de métier de l'accueil. Elle passe devant l'illustration
| dessinée (database/seeders/data/images), qui reste le repli.
|
| ⚠️ ── CE SONT DES IMAGES DE CALAGE ────────────────────────────────────────
|
| LoremFlickr sert des photos Flickr sous licence Creative Commons, choisies
| sur mots-clés : c'est ce qui permet à la vignette d'un service de plomberie
| de montrer de la plomberie plutôt qu'un paysage au hasard. Trois réserves,
| qui ne s'effacent pas parce qu'on les a lues :
|
|   1. La licence CC de la photo servie réclame le nom de son auteur. Il n'est
|      pas connu à l'avance — l'hôte choisit la photo au moment de la requête —
|      et la mention portée ici est donc une mention de classe, pas la mention
|      individuelle que la licence demande.
|   2. L'hôte peut changer la photo d'un jour à l'autre.
|   3. Chaque affichage envoie l'adresse IP du visiteur à un tiers.
|
| Acceptable pour montrer une maquette qui tient debout. Pas pour une mise en
| ligne. Le chemin vers de vraies photos est inchangé et documenté dans
| design/photos/README.md :
|
|     node design/photos/fetch.mjs --par 1
|     php artisan photos:import --config
|     php artisan images:check
|
| REMOTE_IMAGES=false coupe tout et rend la main aux illustrations dessinées.
|
| ── La forme d'une entrée ───────────────────────────────────────────────────
|
|     'Plomberie' => [
|         'url' => 'services/photos/plomberie-1.jpg',   // ou une URL complète
|         'auteur' => 'Prénom Nom',
|         'licence' => 'CC BY 4.0',
|         'source' => 'https://exemple.test/page-de-la-photo',
|     ],
|
| `url` accepte les deux formes : commençant par http, elle est employée telle
| quelle ; sinon elle est résolue sur le disque de médias. Les mentions sont
| rendues dans les mentions légales, et une entrée sans auteur **ni** licence
| ne s'affiche pas du tout — le garde-fou tient même si le commutateur est à
| vrai.
|
*/

$photo = fn (string $motsCles, int $verrou, int $largeur = 800, int $hauteur = 600) => sprintf(
    'https://loremflickr.com/%d/%d/%s/all?lock=%d',
    $largeur,
    $hauteur,
    $motsCles,
    $verrou
);

// La mention de classe des images de calage. Elle dit ce qu'on sait — le fonds
// et le régime de licence — et ne prétend pas nommer un auteur qu'on ignore.
$auteur = 'Photographes Flickr, via LoremFlickr';
$licence = 'Creative Commons, variable selon la photo servie';
$source = 'https://loremflickr.com/';

return [

    /*
    | Le commutateur général. À faux, aucune photographie distante n'est
    | demandée et l'application affiche ses illustrations dessinées.
    */
    'enabled' => filter_var(env_or('REMOTE_IMAGES', true), FILTER_VALIDATE_BOOLEAN),

    /*
    | Le fond du bandeau d'appel aux prestataires, sur l'accueil.
    */
    'cta' => [
        'url' => $photo('artisan,workshop,africa', 4101, 1600, 900),
        'auteur' => $auteur,
        'licence' => $licence,
        'source' => $source,
    ],

    /*
    | Une entrée par métier, indexée par le nom de la catégorie tel qu'il est
    | en base. Les trente et une y sont : une grille où la moitié des vignettes
    | est photographique et l'autre dessinée se lit comme un défaut.
    */
    'categories' => [
        'Aide à domicile' => [
            'url' => $photo('home,care,elderly', 2229),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Animation & DJ' => [
            'url' => $photo('dj,party,music', 2230),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Carrelage' => [
            'url' => $photo('tiles,tiling,floor', 2231),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Climatisation & réfrigération' => [
            'url' => $photo('air,conditioner,technician', 2201),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Coiffure' => [
            'url' => $photo('hair,salon,braids', 2202),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Cours particuliers' => [
            'url' => $photo('tutoring,student,books', 2203),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Couture & stylisme' => [
            'url' => $photo('tailor,sewing,fabric', 2204),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Déménagement' => [
            'url' => $photo('moving,boxes,truck', 2205),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Garde d\'enfants' => [
            'url' => $photo('children,playing,care', 2206),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Gardiennage' => [
            'url' => $photo('security,guard,gate', 2207),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Générateur & énergie solaire' => [
            'url' => $photo('solar,panels,generator', 2208),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Informatique & réparation' => [
            'url' => $photo('computer,repair', 2209),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Installation TV & antenne' => [
            'url' => $photo('satellite,dish,antenna', 2210),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Jardinage' => [
            'url' => $photo('gardening,plants,tools', 2211),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Livraison d\'eau' => [
            'url' => $photo('water,delivery,jerrycan', 2212),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Location de véhicules' => [
            'url' => $photo('car,rental,keys', 2213),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Maquillage & ongles' => [
            'url' => $photo('makeup,manicure,nails', 2214),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Massage & soins' => [
            'url' => $photo('massage,spa,wellness', 2215),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Maçonnerie' => [
            'url' => $photo('mason,bricks,construction', 2216),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Menuiserie' => [
            'url' => $photo('carpenter,woodworking', 2217),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Moto-taxi' => [
            'url' => $photo('motorcycle,taxi,street', 2218),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Mécanique auto & moto' => [
            'url' => $photo('mechanic,garage,engine', 2219),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Ménage' => [
            'url' => $photo('cleaning,housework', 2220),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Peinture' => [
            'url' => $photo('house,painter,paint', 2221),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Photographie & vidéo' => [
            'url' => $photo('photographer,camera', 2222),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Plomberie' => [
            'url' => $photo('plumber,pipes', 2223),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Réparation téléphone' => [
            'url' => $photo('phone,repair,smartphone', 2224),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Soudure & ferronnerie' => [
            'url' => $photo('welding,metalwork', 2225),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Traiteur & cuisine' => [
            'url' => $photo('african,food,cooking', 2226),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Vente de vivres' => [
            'url' => $photo('market,vegetables,africa', 2227),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
        'Électricité' => [
            'url' => $photo('electrician,wiring', 2228),
            'auteur' => $auteur,
            'licence' => $licence,
            'source' => $source,
        ],
    ],

];
