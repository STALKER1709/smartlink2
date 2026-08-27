<?php

/*
|--------------------------------------------------------------------------
| Photographies distantes
|--------------------------------------------------------------------------
|
| Les illustrations de couverture sont dessinées (database/seeders/data/images)
| et les photographies déposées passent par design/photos. Ce fichier ouvre une
| troisième voie : des images servies par un hôte extérieur, sans rien stocker.
|
| ⚠️ Ce sont des images de calage, pas des images de production.
|
| LoremFlickr sert des photos Flickr sous licence Creative Commons, choisies
| sur mots-clés. C\'est ce qui permet à la vignette d\'un service de plomberie
| de montrer de la plomberie plutôt qu\'un paysage au hasard. En contrepartie :
| la licence CC de la photo servie exige presque toujours une attribution que
| la page ne porte pas, l\'hôte peut changer la photo d\'un jour à l\'autre, et
| chaque affichage envoie l\'adresse IP du visiteur à un tiers.
|
| Avant une mise en production réelle, remplacez chaque URL par une image dont
| vous détenez les droits — ou posez REMOTE_IMAGES=false et laissez les
| illustrations dessinées reprendre la main. Elles sont toujours là : chaque
| balise `img` distante s\'efface d\'elle-même si l\'hôte ne répond pas et
| découvre ce qui existait avant. Voir design/photos/SOURCES.md pour les
| banques exploitables en production.
|
| La table est indexée par le **nom** de la catégorie, tel qu\'il est en base.
| Volontairement séparée de celle des illustrations dessinées
| (database/seeders/data/images/categories.json) : celle-là ne couvre que les
| quatorze métiers pour lesquels un motif a été dessiné, et s\'y adosser
| laissait onze catégories sans photo au milieu d\'une grille qui en avait.
| Une catégorie absente d\'ici garde son pictogramme, ce n\'est pas une erreur.
|
*/

$photo = fn (string $motsCles, int $verrou, int $largeur = 800, int $hauteur = 600) => sprintf(
    'https://loremflickr.com/%d/%d/%s/all?lock=%d',
    $largeur,
    $hauteur,
    $motsCles,
    $verrou
);

return [

    /*
    | Le commutateur général. À faux, aucune requête ne part vers un tiers et
    | l'application retrouve exactement le rendu qu'elle avait sans ce fichier.
    */
    'enabled' => filter_var(env_or('REMOTE_IMAGES', true), FILTER_VALIDATE_BOOLEAN),

    /*
    | Le bandeau d'appel aux prestataires. Le verrou (`lock`) fige la photo :
    | sans lui, l'hôte en sert une différente à chaque chargement et la page
    | change de visage entre deux visites.
    */
    'cta' => env_or('REMOTE_IMAGE_CTA', $photo('artisan,workshop,africa', 4101, 1600, 900)),

    /*
    | Une photo par métier.
    */
    'categories' => [
        'Aide à domicile' => $photo('home,care,elderly', 2229),
        'Animation & DJ' => $photo('dj,party,music', 2230),
        'Carrelage' => $photo('tiles,tiling,floor', 2231),
        'Climatisation & réfrigération' => $photo('air,conditioner,technician', 2201),
        'Coiffure' => $photo('hair,salon,braids', 2202),
        'Cours particuliers' => $photo('tutoring,student,books', 2203),
        'Couture & stylisme' => $photo('tailor,sewing,fabric', 2204),
        'Déménagement' => $photo('moving,boxes,truck', 2205),
        'Garde d\'enfants' => $photo('children,playing,care', 2206),
        'Gardiennage' => $photo('security,guard,gate', 2207),
        'Générateur & énergie solaire' => $photo('solar,panels,generator', 2208),
        'Informatique & réparation' => $photo('computer,repair', 2209),
        'Installation TV & antenne' => $photo('satellite,dish,antenna', 2210),
        'Jardinage' => $photo('gardening,plants,tools', 2211),
        'Livraison d\'eau' => $photo('water,delivery,jerrycan', 2212),
        'Location de véhicules' => $photo('car,rental,keys', 2213),
        'Maquillage & ongles' => $photo('makeup,manicure,nails', 2214),
        'Massage & soins' => $photo('massage,spa,wellness', 2215),
        'Maçonnerie' => $photo('mason,bricks,construction', 2216),
        'Menuiserie' => $photo('carpenter,woodworking', 2217),
        'Moto-taxi' => $photo('motorcycle,taxi,street', 2218),
        'Mécanique auto & moto' => $photo('mechanic,garage,engine', 2219),
        'Ménage' => $photo('cleaning,housework', 2220),
        'Peinture' => $photo('house,painter,paint', 2221),
        'Photographie & vidéo' => $photo('photographer,camera', 2222),
        'Plomberie' => $photo('plumber,pipes', 2223),
        'Réparation téléphone' => $photo('phone,repair,smartphone', 2224),
        'Soudure & ferronnerie' => $photo('welding,metalwork', 2225),
        'Traiteur & cuisine' => $photo('african,food,cooking', 2226),
        'Vente de vivres' => $photo('market,vegetables,africa', 2227),
        'Électricité' => $photo('electrician,wiring', 2228),
    ],

];
