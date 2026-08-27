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
    'cta' => [
        'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Rue_du_march%C3%A9_Mboppi_%C3%A0_Douala1.jpg?width=1600',
        'auteur' => null,
        'licence' => null,
        'source' => 'https://commons.wikimedia.org/wiki/File:Rue_du_march%C3%A9_Mboppi_%C3%A0_Douala1.jpg',
    ],

    /*
    | Une entrée par métier, indexée par le nom de la catégorie tel qu'il est
    | en base.
    |
    | ⚠️ Les entrées remplies sont des propositions, pas un choix.
    | Leurs fichiers existent — leur titre vient de l'index de Wikimedia
    | Commons — mais personne ne les a regardés, et leur auteur comme leur
    | licence restent à null. `php artisan images:credits` va les chercher sur
    | l'API de Commons et rend le bloc complété ; `php artisan images:check`
    | refuse de laisser passer une entrée sans mention. C'est pour cela que
    | REMOTE_IMAGES reste à faux : ouvrez les pages source, regardez les
    | photos, complétez les mentions, puis décidez.
    |
    | Vingt-trois métiers sur trente et un. Les huit derniers — aide à
    | domicile, animation & DJ, carrelage, déménagement, gardiennage,
    | installation TV & antenne, location de véhicules, réparation téléphone —
    | n'ont rendu que des pages de catégorie et des logos de marque. Ils gardent
    | leur illustration dessinée.
    */
    'categories' => [
        'Aide à domicile' => null,
        'Animation & DJ' => null,
        'Carrelage' => null,
        'Climatisation & réfrigération' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Inside_of_split_type_ac_outdoor_unit.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Inside_of_split_type_ac_outdoor_unit.jpg',
        ],
        'Coiffure' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Hairdresser_in_Cameroon.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Hairdresser_in_Cameroon.jpg',
        ],
        'Cours particuliers' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/School_teacher.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:School_teacher.jpg',
        ],
        'Couture & stylisme' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Seemstress_Sewing_In_Ghana_Africa.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Seemstress_Sewing_In_Ghana_Africa.jpg',
        ],
        'Déménagement' => null,
        'Garde d\'enfants' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Kids_at_daycare.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Kids_at_daycare.jpg',
        ],
        'Gardiennage' => null,
        'Générateur & énergie solaire' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Solar_panel_installer.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Solar_panel_installer.jpg',
        ],
        'Informatique & réparation' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/African_software_developer_at_work%2C_Nigeria.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:African_software_developer_at_work%2C_Nigeria.jpg',
        ],
        'Installation TV & antenne' => null,
        'Jardinage' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/The_gardener.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:The_gardener.jpg',
        ],
        'Livraison d\'eau' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Women_gathered_to_fetch_Water_at_a_borehole_in_Northern_Ghana_01.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Women_gathered_to_fetch_Water_at_a_borehole_in_Northern_Ghana_01.jpg',
        ],
        'Location de véhicules' => null,
        'Maquillage & ongles' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Makeup_artist.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Makeup_artist.jpg',
        ],
        'Massage & soins' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Massage-hand-4.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Massage-hand-4.jpg',
        ],
        'Maçonnerie' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Mason_on_the_scaffold_in_Accra.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Mason_on_the_scaffold_in_Accra.jpg',
        ],
        'Menuiserie' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Carpenter%27s_workshop_in_Cap-Haitien.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Carpenter%27s_workshop_in_Cap-Haitien.jpg',
        ],
        'Moto-taxi' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Moto_taxi_Okada_%C3%A0_Madina_%28Accra%29.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Moto_taxi_Okada_%C3%A0_Madina_%28Accra%29.jpg',
        ],
        'Mécanique auto & moto' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Ghana_Mechanic_Working.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Ghana_Mechanic_Working.jpg',
        ],
        'Ménage' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/African_woman_washing_clothes.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:African_woman_washing_clothes.jpg',
        ],
        'Peinture' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Man_Painting_Wall.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Man_Painting_Wall.jpg',
        ],
        'Photographie & vidéo' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Pictures_taken_during_Alvan_Photo_Walk_for_Wiki_Loves_Africa_2022_Nigeria_Campus_Photo_Fest_02.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Pictures_taken_during_Alvan_Photo_Walk_for_Wiki_Loves_Africa_2022_Nigeria_Campus_Photo_Fest_02.jpg',
        ],
        'Plomberie' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Plumber_at_work.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Plumber_at_work.jpg',
        ],
        'Réparation téléphone' => null,
        'Soudure & ferronnerie' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Baliwagenyo_welder_with_green_hard_hat_working_atop_a_petrol_station_metal_structure_01.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Baliwagenyo_welder_with_green_hard_hat_working_atop_a_petrol_station_metal_structure_01.jpg',
        ],
        'Traiteur & cuisine' => [
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Cooking_in_a_three_stone_fire_stove_in_Oyam_District_02.jpg?width=1200',
            'auteur' => null,
            'licence' => null,
            'source' => 'https://commons.wikimedia.org/wiki/File:Cooking_in_a_three_stone_fire_stove_in_Oyam_District_02.jpg',
        ],
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
