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
    |
    | ⚠️ Les sept entrées remplies sont des propositions, pas un choix.
    | Leurs fichiers existent — leur titre vient de l'index de Wikimedia
    | Commons — mais personne ne les a regardés, et leur auteur comme leur
    | licence restent à null. `php artisan images:credits` va les chercher sur
    | l'API de Commons et rend le bloc complété ; `php artisan images:check`
    | refuse de laisser passer une entrée sans mention. C'est pour cela que
    | REMOTE_IMAGES reste à faux : ouvrez les sept pages source, regardez les
    | photos, complétez les mentions, puis décidez.
    */
    'categories' => [
        'Aide à domicile' => null,
        'Animation & DJ' => null,
        'Carrelage' => null,
        'Climatisation & réfrigération' => null,
        'Coiffure' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Hairdresser_in_Cameroon.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Hairdresser_in_Cameroon.jpg',
        ],
        'Cours particuliers' => null,
        'Couture & stylisme' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Seemstress_Sewing_In_Ghana_Africa.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Seemstress_Sewing_In_Ghana_Africa.jpg',
        ],
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
        'Maçonnerie' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Mason_on_the_scaffold_in_Accra.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Mason_on_the_scaffold_in_Accra.jpg',
        ],
        'Menuiserie' => null,
        'Moto-taxi' => null,
        'Mécanique auto & moto' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Ghana_Mechanic_Working.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Ghana_Mechanic_Working.jpg',
        ],
        'Ménage' => null,
        'Peinture' => null,
        'Photographie & vidéo' => null,
        'Plomberie' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Plumber_at_work.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Plumber_at_work.jpg',
        ],
        'Réparation téléphone' => null,
        'Soudure & ferronnerie' => null,
        'Traiteur & cuisine' => null,
        'Vente de vivres' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/March%C3%A9_de_Bonamoussadi_%C3%A0_Douala.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:March%C3%A9_de_Bonamoussadi_%C3%A0_Douala.jpg',
        ],
        'Électricité' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Electrician_Working.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Electrician_Working.jpg',
        ],
    ],

];
