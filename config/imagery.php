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
| ── La forme d'une entrée ───────────────────────────────────────────────────
|
|     'Plomberie' => [
|         'url' => 'services/photos/plomberie-1.jpg',   // sur le disque de médias
|         'auteur' => 'Équipe SmartLink',
|         'licence' => 'Propriété SmartLink',
|         'source' => null,
|     ],
|
|     'Coiffure' => [
|         'url' => 'https://exemple.test/coiffure.jpg', // ou une URL complète
|         'auteur' => 'Prénom Nom',
|         'licence' => 'CC BY 4.0',
|         'source' => 'https://exemple.test/page-de-la-photo',
|     ],
|
| `url` accepte les deux formes : commençant par http, elle est employée telle
| quelle ; sinon elle est résolue sur le disque de médias, comme le reste des
| fichiers déposés. Une entrée à `null` n'est pas une erreur — le métier garde
| son illustration dessinée.
|
| ── Les trois autres champs ne sont pas décoratifs ──────────────────────────
|
| Presque toutes les licences libres exigent que le nom de l'auteur et la
| licence soient portés par la page qui affiche la photo. Ils sont donc rendus
| dans les mentions légales, et `php artisan images:check` refuse une entrée
| qui porte une URL sans eux. Une photo dont vous détenez les droits se déclare
| de la même façon, avec votre propre nom.
|
| ── Comment remplir cette table ─────────────────────────────────────────────
|
|     node design/photos/fetch.mjs --par 1     # rapatrie et note la provenance
|     php artisan photos:import                # dépose sur le disque de médias
|     php artisan images:check                 # vérifie URL et mentions
|
| Voir design/photos/README.md et design/photos/SOURCES.md.
|
*/

return [

    /*
    | Le commutateur général. À faux, aucune photographie distante n'est
    | demandée et l'application affiche ses illustrations dessinées. Il reste
    | à faux tant que la table n'est pas remplie : une page à moitié
    | photographique et à moitié dessinée se lit comme un défaut.
    */
    'enabled' => filter_var(env_or('REMOTE_IMAGES', false), FILTER_VALIDATE_BOOLEAN),

    /*
    | Le fond du bandeau d'appel aux prestataires, sur l'accueil.
    */
    'cta' => null,

    /*
    | Une entrée par métier, indexée par le nom de la catégorie tel qu'il est
    | en base.
    */
    'categories' => [
        'Aide à domicile' => null,
        'Animation & DJ' => null,
        'Carrelage' => null,
        'Climatisation & réfrigération' => null,
        'Coiffure' => null,
        'Cours particuliers' => null,
        'Couture & stylisme' => null,
        'Déménagement' => null,
        'Garde d\'enfants' => null,
        'Gardiennage' => null,
        'Générateur & énergie solaire' => null,
        'Informatique & réparation' => null,
        'Installation TV & antenne' => null,
        'Jardinage' => null,
        'Livraison d\'eau' => null,
        'Location de véhicules' => null,
        'Maquillage & ongles' => null,
        'Massage & soins' => null,
        'Maçonnerie' => null,
        'Menuiserie' => null,
        'Moto-taxi' => null,
        'Mécanique auto & moto' => null,
        'Ménage' => null,
        'Peinture' => null,
        'Photographie & vidéo' => null,
        'Plomberie' => null,
        'Réparation téléphone' => null,
        'Soudure & ferronnerie' => null,
        'Traiteur & cuisine' => null,
        'Vente de vivres' => null,
        'Électricité' => null,
    ],

];
