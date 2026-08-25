<?php

/*
 * Contenu de démonstration, écrit à la main.
 *
 * Les fabriques (`database/factories`) tirent leurs textes de Faker en
 * « en_US » : des noms anglophones et des descriptions en faux latin. C'est
 * sans conséquence pour la suite de tests, qui ne lit pas ces textes, mais une
 * plateforme publique qui affiche du « Lorem ipsum » a l'air en panne.
 *
 * Ces données-là sont donc écrites, pas tirées au sort : des métiers, des
 * villes et des quartiers réels du Cameroun, des prix plausibles en FCFA, et
 * des annonces qui disent ce que le prestataire fait vraiment.
 *
 * Les paliers d'abonnement sont répartis exprès sur tous les états possibles
 * — essai, Pro, Essentiel, gratuit, expiré — pour que chaque affichage de
 * l'interface se voie sur des données réelles plutôt qu'en imagination.
 */

return [
    'providers' => [
        [
            'name' => 'Serge Ndongo',
            'email' => 'serge.ndongo@demo.smartlink.cm',
            'phone' => '699100201',
            'business' => 'Ndongo Plomberie Express',
            'category' => 'Plomberie',
            'city' => 'Douala',
            'quarter' => 'Bonamoussadi',
            'plan' => 'pro',
            'verified' => true,
            'description' => "Quinze ans de métier à Douala. J'interviens sur les fuites, "
                ."les chauffe-eau et l'installation complète de salles de bain. Devis gratuit "
                .'avant tout démarrage, déplacement compris dans le cinquième arrondissement.',
            'services' => [
                [
                    'title' => 'Recherche et réparation de fuite',
                    'description' => 'Détection de la fuite, remplacement du raccord ou de la section '
                        ."de tuyau, remise en eau et vérification de la pression. J'apporte mon "
                        ."matériel ; si une pièce manque, je préviens du coût avant de l'acheter.",
                    'price' => 8000,
                    'unit' => 'par intervention',
                ],
                [
                    'title' => 'Installation de chauffe-eau électrique',
                    'description' => 'Pose murale, raccordement à l\'arrivée d\'eau et au tableau '
                        .'électrique, purge et mise en service. Le prix couvre la pose seule : le '
                        .'chauffe-eau reste à votre charge, je peux vous accompagner à l\'achat.',
                    'price' => 25000,
                    'unit' => 'par intervention',
                ],
                [
                    'title' => 'Plomberie complète de salle de bain',
                    'description' => 'Alimentation, évacuation, pose des sanitaires et de la robinetterie. '
                        .'Chantier de deux à quatre jours selon la surface. Je travaille avec un '
                        .'carreleur si le sol est à refaire.',
                    'price' => 150000,
                    'unit' => 'par chantier',
                ],
            ],
        ],
        [
            'name' => 'Pascaline Ngo Bassong',
            'email' => 'pascaline.bassong@demo.smartlink.cm',
            'phone' => '699100202',
            'business' => 'Pascaline Coiffure à domicile',
            'category' => 'Coiffure',
            'city' => 'Douala',
            'quarter' => 'Akwa',
            'plan' => 'pro',
            'verified' => true,
            'description' => 'Coiffeuse depuis 2014, je me déplace chez vous avec tout le matériel. '
                .'Tresses, tissages, défrisage, soins du cuir chevelu. Créneaux le soir et le '
                .'dimanche pour celles qui travaillent en semaine.',
            'services' => [
                [
                    'title' => 'Tresses collées avec mèches',
                    'description' => 'Nattes collées classiques ou motifs au choix, mèches fournies. '
                        .'Comptez trois à quatre heures selon la longueur souhaitée. Shampooing '
                        .'et soin avant pose inclus.',
                    'price' => 7000,
                    'unit' => 'par prestation',
                ],
                [
                    'title' => 'Pose de tissage',
                    'description' => 'Tressage de la base, couture du tissage et coupe de finition. '
                        .'Le tissage n\'est pas fourni ; je peux vous conseiller sur la qualité '
                        .'avant achat pour éviter les mauvaises surprises.',
                    'price' => 10000,
                    'unit' => 'par prestation',
                ],
            ],
        ],
        [
            'name' => 'Alphonse Tchoumi',
            'email' => 'alphonse.tchoumi@demo.smartlink.cm',
            'phone' => '699100203',
            'business' => 'Tchoumi Électricité Bâtiment',
            'category' => 'Électricité',
            'city' => 'Yaoundé',
            'quarter' => 'Bastos',
            'plan' => 'pro',
            'verified' => true,
            'description' => 'Électricien bâtiment formé au CETIC de Yaoundé. Installations neuves, '
                .'mises aux normes et dépannage. Je fournis un schéma du tableau après chaque '
                .'chantier, pour que vous sachiez ce qui a été fait.',
            'services' => [
                [
                    'title' => 'Dépannage électrique urgent',
                    'description' => 'Panne totale, disjoncteur qui saute, prise qui chauffe : '
                        .'diagnostic sur place et remise en service quand la pièce est disponible. '
                        .'Intervention dans la journée sur Yaoundé.',
                    'price' => 10000,
                    'unit' => 'par intervention',
                ],
                [
                    'title' => 'Installation électrique complète',
                    'description' => 'Câblage, pose du tableau, prises et points lumineux pour une '
                        .'maison neuve ou une rénovation. Devis chiffré pièce par pièce avant '
                        .'de commencer.',
                    'price' => 350000,
                    'unit' => 'par chantier',
                ],
                [
                    'title' => 'Installation de panneaux solaires',
                    'description' => 'Kit solaire pour tenir les coupures : panneaux, régulateur, '
                        .'batteries et onduleur. Je dimensionne selon ce que vous voulez '
                        .'alimenter — éclairage seul, ou éclairage plus réfrigérateur.',
                    'price' => 450000,
                    'unit' => 'par installation',
                ],
            ],
        ],
        [
            'name' => 'Georgette Mekonda',
            'email' => 'georgette.mekonda@demo.smartlink.cm',
            'phone' => '699100204',
            'business' => 'Maison Nette Services',
            'category' => 'Ménage',
            'city' => 'Yaoundé',
            'quarter' => 'Mvog-Ada',
            'plan' => 'essential',
            'verified' => true,
            'description' => 'Équipe de trois personnes pour le ménage régulier et les grands '
                .'nettoyages. Nous travaillons pour des particuliers et de petits bureaux. '
                .'Produits fournis, tarif dégressif à partir de deux passages par semaine.',
            'services' => [
                [
                    'title' => 'Ménage régulier à domicile',
                    'description' => 'Sols, sanitaires, cuisine, poussière et vitres accessibles. '
                        .'Une demi-journée par passage pour un appartement de trois pièces. '
                        .'Même personne à chaque fois, pour que vous ne réexpliquiez pas tout.',
                    'price' => 5000,
                    'unit' => 'par passage',
                ],
                [
                    'title' => 'Grand nettoyage de fin de chantier',
                    'description' => 'Retrait des gravats fins, décapage des sols, nettoyage des '
                        .'traces de peinture et des vitres. Prévoir une journée complète pour '
                        .'une maison de quatre pièces.',
                    'price' => 40000,
                    'unit' => 'par chantier',
                ],
            ],
        ],
        [
            'name' => 'Rodrigue Fotso',
            'email' => 'rodrigue.fotso@demo.smartlink.cm',
            'phone' => '699100205',
            'business' => 'Fotso Froid & Climatisation',
            'category' => 'Climatisation & réfrigération',
            'city' => 'Douala',
            'quarter' => 'Deïdo',
            'plan' => 'essential',
            'verified' => false,
            'description' => 'Frigoriste installé à Deïdo. Climatiseurs split, chambres froides et '
                .'réfrigérateurs. Je récupère et recycle le gaz réglementairement plutôt que '
                .'de le relâcher.',
            'services' => [
                [
                    'title' => 'Pose de climatiseur split',
                    'description' => 'Perçage, fixation des deux unités, liaison frigorifique, tirage '
                        .'au vide et mise en service. Le prix est pour une liaison de trois '
                        .'mètres ; au-delà, le tuyau supplémentaire est facturé au mètre.',
                    'price' => 30000,
                    'unit' => 'par appareil',
                ],
                [
                    'title' => 'Entretien et recharge de gaz',
                    'description' => 'Nettoyage des filtres et de l\'échangeur, contrôle de la '
                        .'pression, recharge si nécessaire. Un entretien par an suffit à '
                        .'diviser la consommation par deux sur un appareil encrassé.',
                    'price' => 15000,
                    'unit' => 'par appareil',
                ],
            ],
        ],
        [
            'name' => 'Marthe Abena',
            'email' => 'marthe.abena@demo.smartlink.cm',
            'phone' => '699100206',
            'business' => 'Chez Marthe — Traiteur',
            'category' => 'Traiteur & cuisine',
            'city' => 'Yaoundé',
            'quarter' => 'Nlongkak',
            'plan' => 'essential',
            'verified' => true,
            'description' => 'Cuisine camerounaise pour mariages, deuils et réunions de famille. '
                .'Ndolè, poulet DG, eru, sauce arachide. Je livre chaud dans des marmites '
                .'isothermes et je récupère le matériel le lendemain.',
            'services' => [
                [
                    'title' => 'Repas complet pour événement',
                    'description' => 'Plat principal, accompagnement et boisson locale, servis sur '
                        .'place ou livrés. Prix par personne, dégressif au-delà de cinquante '
                        .'couverts. Menu à convenir une semaine avant.',
                    'price' => 3500,
                    'unit' => 'par personne',
                ],
                [
                    'title' => 'Marmite de ndolè pour vingt personnes',
                    'description' => 'Ndolè aux crevettes et à la viande, préparé le matin même. '
                        .'Livraison dans Yaoundé comprise. Idéal pour une réunion de famille '
                        .'sans mobiliser la cuisine de la maison.',
                    'price' => 45000,
                    'unit' => 'par marmite',
                ],
            ],
        ],
        [
            'name' => 'Blaise Mbarga',
            'email' => 'blaise.mbarga@demo.smartlink.cm',
            'phone' => '699100207',
            'business' => 'Atelier Mbarga Menuiserie',
            'category' => 'Menuiserie',
            'city' => 'Yaoundé',
            'quarter' => 'Mendong',
            'plan' => 'free',
            'verified' => false,
            'description' => 'Atelier de menuiserie bois à Mendong. Meubles sur mesure, portes, '
                ."placards. Je travaille l'iroko et le sapelli, et je montre le bois avant "
                .'de commencer pour qu\'il n\'y ait pas de discussion à la livraison.',
            'services' => [
                [
                    'title' => 'Meuble sur mesure en bois massif',
                    'description' => "Lit, armoire, table ou bibliothèque, dessinés d'après vos "
                        .'dimensions. Délai de deux à trois semaines selon la pièce. Un acompte '
                        .'sert à acheter le bois, le solde à la livraison.',
                    'price' => 120000,
                    'unit' => 'par meuble',
                ],
            ],
        ],
        [
            'name' => 'Estelle Nguema',
            'email' => 'estelle.nguema@demo.smartlink.cm',
            'phone' => '699100208',
            'business' => 'Estelle Couture',
            'category' => 'Couture & stylisme',
            'city' => 'Douala',
            'quarter' => 'New-Bell',
            'plan' => 'free',
            'verified' => false,
            'description' => 'Couturière depuis huit ans. Tenues traditionnelles en pagne, chemises '
                .'et robes sur mesure, retouches. Je prends les mesures chez vous si vous '
                .'ne pouvez pas passer à l\'atelier.',
            'services' => [
                [
                    'title' => 'Tenue sur mesure en pagne',
                    'description' => 'Coupe et couture d\'une tenue complète d\'après vos mesures. '
                        .'Le pagne est à fournir ; comptez six mètres pour un ensemble. '
                        .'Un essayage à mi-parcours évite les retouches à la fin.',
                    'price' => 15000,
                    'unit' => 'par tenue',
                ],
            ],
        ],
        [
            'name' => 'Cyrille Kamdem',
            'email' => 'cyrille.kamdem@demo.smartlink.cm',
            'phone' => '699100209',
            'business' => 'Kamdem Bâtiment',
            'category' => 'Maçonnerie',
            'city' => 'Bafoussam',
            'quarter' => 'Tamdja',
            'plan' => 'essential',
            'verified' => true,
            'description' => 'Maçon et chef de chantier à Bafoussam. Fondations, élévation, dalles '
                .'et enduits. Je travaille avec une équipe fixe de cinq personnes, ce qui '
                .'évite les chantiers abandonnés en cours de route.',
            'services' => [
                [
                    'title' => 'Élévation de murs en parpaings',
                    'description' => 'Montage des murs au mètre carré, chaînages compris. Les '
                        .'matériaux sont à votre charge ; je vous donne la liste et les '
                        .'quantités exactes après le métré.',
                    'price' => 4500,
                    'unit' => 'par mètre carré',
                ],
                [
                    'title' => 'Coulage de dalle béton',
                    'description' => 'Coffrage, ferraillage et coulage, avec vibration pour éviter '
                        .'les nids de gravier. Prix de la main-d\'œuvre au mètre carré, béton '
                        .'non compris.',
                    'price' => 6000,
                    'unit' => 'par mètre carré',
                ],
            ],
        ],
        [
            'name' => 'Ibrahim Sali',
            'email' => 'ibrahim.sali@demo.smartlink.cm',
            'phone' => '699100210',
            'business' => 'Sali Eau Potable',
            'category' => 'Livraison d\'eau',
            'city' => 'Garoua',
            'quarter' => 'Poumpoumré',
            'plan' => 'essential',
            'verified' => false,
            'description' => 'Livraison d\'eau potable à Garoua, du bidon de vingt litres à la '
                .'citerne. Je livre tôt le matin et en fin d\'après-midi, pour éviter les '
                .'heures les plus chaudes.',
            'services' => [
                [
                    'title' => 'Livraison de bidons de 20 litres',
                    'description' => 'Eau traitée, bidons consignés et rincés à chaque tournée. '
                        .'Prix à l\'unité, livraison comprise dans le quartier. Abonnement '
                        .'possible pour une livraison fixe chaque semaine.',
                    'price' => 500,
                    'unit' => 'par bidon',
                ],
                [
                    'title' => 'Remplissage de citerne',
                    'description' => 'Camion-citerne pour remplir un réservoir de maison ou de '
                        .'chantier. Comptez une heure entre l\'appel et l\'arrivée selon la '
                        .'distance.',
                    'price' => 35000,
                    'unit' => 'par citerne',
                ],
            ],
        ],
        [
            'name' => 'Armand Nkeng',
            'email' => 'armand.nkeng@demo.smartlink.cm',
            'phone' => '699100211',
            'business' => 'Nkeng Informatique',
            'category' => 'Informatique & réparation',
            'city' => 'Bamenda',
            'quarter' => 'Commercial Avenue',
            'plan' => 'trial',
            'verified' => false,
            'description' => 'Réparation d\'ordinateurs et de petits réseaux d\'entreprise. '
                .'Changement d\'écran, remplacement de disque, récupération de données, '
                .'installation de systèmes. Diagnostic gratuit avant devis.',
            'services' => [
                [
                    'title' => 'Diagnostic et réparation d\'ordinateur',
                    'description' => 'Panne au démarrage, lenteur, surchauffe ou écran cassé : '
                        .'diagnostic puis devis avant toute intervention. Les pièces sont '
                        .'facturées à part, sur justificatif.',
                    'price' => 5000,
                    'unit' => 'par diagnostic',
                ],
                [
                    'title' => 'Installation de réseau et Wi-Fi',
                    'description' => 'Câblage, configuration du routeur et couverture Wi-Fi pour '
                        .'un bureau ou un cybercafé. Je mesure la couverture pièce par pièce '
                        .'avant de fixer l\'emplacement des bornes.',
                    'price' => 60000,
                    'unit' => 'par installation',
                ],
            ],
        ],
        [
            'name' => 'Sylvie Atangana',
            'email' => 'sylvie.atangana@demo.smartlink.cm',
            'phone' => '699100212',
            'business' => 'Cours Atangana',
            'category' => 'Cours particuliers',
            'city' => 'Yaoundé',
            'quarter' => 'Ngoa-Ekelle',
            'plan' => 'trial',
            'verified' => true,
            'description' => 'Enseignante de mathématiques en lycée, je donne des cours du soir et '
                .'du week-end. Préparation au BEPC et au baccalauréat, remise à niveau. '
                .'Un bilan écrit est remis aux parents chaque mois.',
            'services' => [
                [
                    'title' => 'Cours de mathématiques, collège et lycée',
                    'description' => 'Séances d\'une heure et demie chez l\'élève, programme '
                        .'camerounais. Je commence par un test de positionnement pour cibler '
                        .'ce qui manque vraiment plutôt que de tout reprendre.',
                    'price' => 5000,
                    'unit' => 'par séance',
                ],
                [
                    'title' => 'Préparation intensive au baccalauréat',
                    'description' => 'Programme de huit semaines : révision par chapitre, annales '
                        .'corrigées et deux épreuves blanches en conditions réelles. '
                        .'Trois séances par semaine.',
                    'price' => 90000,
                    'unit' => 'par programme',
                ],
            ],
        ],
        [
            'name' => 'Didier Owona',
            'email' => 'didier.owona@demo.smartlink.cm',
            'phone' => '699100213',
            'business' => 'Owona Studio Photo',
            'category' => 'Photographie & vidéo',
            'city' => 'Douala',
            'quarter' => 'Bonapriso',
            'plan' => 'expired',
            'verified' => false,
            'description' => 'Photographe d\'événements depuis dix ans : mariages, baptêmes, '
                .'remises de diplômes. Les photos retouchées sont livrées sur clé USB dans '
                .'les sept jours, et je garde les fichiers un an.',
            'services' => [
                [
                    'title' => 'Reportage photo de mariage',
                    'description' => 'Couverture de la mairie à la réception, deux photographes. '
                        .'Trois cents photos retouchées livrées sur clé USB, plus un album '
                        .'imprimé de trente pages.',
                    'price' => 250000,
                    'unit' => 'par événement',
                ],
            ],
        ],
        [
            'name' => 'Prosper Ekani',
            'email' => 'prosper.ekani@demo.smartlink.cm',
            'phone' => '699100214',
            'business' => 'Garage Ekani',
            'category' => 'Mécanique auto & moto',
            'city' => 'Douala',
            'quarter' => 'Bépanda',
            'plan' => 'pro',
            'verified' => true,
            'description' => 'Garage à Bépanda, spécialisé Toyota et véhicules d\'occasion importés. '
                .'Mécanique générale, embrayage, suspension, diagnostic électronique. '
                .'Les pièces remplacées vous sont rendues.',
            'services' => [
                [
                    'title' => 'Vidange et révision complète',
                    'description' => 'Huile moteur, filtres à huile, à air et à carburant, contrôle '
                        .'des niveaux, des freins et de la suspension. Prix hors pièces, '
                        .'annoncé après le contrôle.',
                    'price' => 12000,
                    'unit' => 'par véhicule',
                ],
                [
                    'title' => 'Diagnostic électronique',
                    'description' => 'Lecture des codes défaut à la valise, interprétation et devis '
                        .'de réparation. Le montant du diagnostic est déduit si la réparation '
                        .'est faite au garage.',
                    'price' => 8000,
                    'unit' => 'par véhicule',
                ],
            ],
        ],
    ],

    'clients' => [
        ['name' => 'Aïssatou Bello', 'email' => 'aissatou.bello@demo.smartlink.cm', 'phone' => '699200301', 'city' => 'Douala'],
        ['name' => 'Hervé Manga', 'email' => 'herve.manga@demo.smartlink.cm', 'phone' => '699200302', 'city' => 'Yaoundé'],
        ['name' => 'Nadège Tsafack', 'email' => 'nadege.tsafack@demo.smartlink.cm', 'phone' => '699200303', 'city' => 'Douala'],
        ['name' => 'Olivier Beyala', 'email' => 'olivier.beyala@demo.smartlink.cm', 'phone' => '699200304', 'city' => 'Yaoundé'],
        ['name' => 'Clarisse Mbida', 'email' => 'clarisse.mbida@demo.smartlink.cm', 'phone' => '699200305', 'city' => 'Bafoussam'],
        ['name' => 'Boubakary Hamadou', 'email' => 'boubakary.hamadou@demo.smartlink.cm', 'phone' => '699200306', 'city' => 'Garoua'],
        ['name' => 'Sandrine Enow', 'email' => 'sandrine.enow@demo.smartlink.cm', 'phone' => '699200307', 'city' => 'Bamenda'],
        ['name' => 'Thierry Njoya', 'email' => 'thierry.njoya@demo.smartlink.cm', 'phone' => '699200308', 'city' => 'Douala'],
    ],

    /*
     * Demandes de service. Chaque entrée cite un prestataire et un client par
     * leur adresse, et le statut visé : l'ensemble couvre tout le cycle de vie
     * d'une demande, pour que les écrans « en cours », « refusée » et
     * « terminée » aient tous quelque chose à montrer.
     */
    'requests' => [
        [
            'client' => 'aissatou.bello@demo.smartlink.cm',
            'provider' => 'serge.ndongo@demo.smartlink.cm',
            'status' => 'completed',
            'days_ago' => 21,
            'message' => "Bonjour, j'ai une fuite sous l'évier de la cuisine depuis deux jours, "
                ."l'eau s'écoule dès que j'ouvre le robinet. Pouvez-vous passer cette semaine ?",
            'reply' => 'Bonjour, je peux passer jeudi matin vers 9h. Coupez l\'arrivée d\'eau '
                .'sous l\'évier en attendant si vous trouvez le robinet d\'arrêt.',
            'review' => ['rating' => 5, 'comment' => 'Ponctuel et propre. La fuite venait du siphon, '
                .'réparé en une heure. Il a expliqué ce qu\'il faisait au fur et à mesure.'],
        ],
        [
            'client' => 'herve.manga@demo.smartlink.cm',
            'provider' => 'alphonse.tchoumi@demo.smartlink.cm',
            'status' => 'completed',
            'days_ago' => 34,
            'message' => 'Le disjoncteur général saute plusieurs fois par jour depuis que nous '
                .'avons branché le congélateur. Besoin d\'un diagnostic.',
            'reply' => 'Je passe demain après-midi. Laissez le congélateur branché, il faut que '
                .'la panne se reproduise pour que je la trouve.',
            'review' => ['rating' => 5, 'comment' => 'Problème trouvé en vingt minutes : une prise '
                .'mal serrée. Il a aussi repris le tableau qui était dangereux. Travail sérieux.'],
        ],
        [
            'client' => 'nadege.tsafack@demo.smartlink.cm',
            'provider' => 'pascaline.bassong@demo.smartlink.cm',
            'status' => 'completed',
            'days_ago' => 12,
            'message' => 'Bonjour, je voudrais des tresses collées pour samedi. Vous vous déplacez '
                .'jusqu\'à Makepe ?',
            'reply' => 'Oui je me déplace à Makepe. Samedi 14h vous convient ? Prévoyez les mèches '
                .'ou je les apporte, dites-moi.',
            'review' => ['rating' => 4, 'comment' => 'Très belles tresses et prix respecté. '
                .'Elle est arrivée avec une demi-heure de retard, mais elle avait prévenu.'],
        ],
        [
            'client' => 'olivier.beyala@demo.smartlink.cm',
            'provider' => 'georgette.mekonda@demo.smartlink.cm',
            'status' => 'completed',
            'days_ago' => 8,
            'message' => 'Nous cherchons quelqu\'un pour le ménage deux fois par semaine dans un '
                .'appartement de trois pièces à Mvog-Ada.',
            'reply' => 'Bonjour, c\'est tout à fait dans notre zone. Je peux passer voir '
                .'l\'appartement avant de vous confirmer le tarif.',
            'review' => ['rating' => 5, 'comment' => 'Équipe régulière et de confiance. '
                .'Trois mois que ça dure, jamais un problème.'],
        ],
        [
            'client' => 'thierry.njoya@demo.smartlink.cm',
            'provider' => 'prosper.ekani@demo.smartlink.cm',
            'status' => 'completed',
            'days_ago' => 45,
            'message' => 'Ma Corolla fait un bruit à l\'embrayage quand je démarre en côte. '
                .'Vous prenez les rendez-vous le samedi ?',
            'reply' => 'Oui, samedi matin. Amenez-la tôt, il faut la mettre sur le pont pour '
                .'écouter correctement.',
            'review' => ['rating' => 4, 'comment' => 'Bon diagnostic et prix annoncé tenu. '
                .'Le garage est un peu difficile à trouver la première fois.'],
        ],
        [
            'client' => 'clarisse.mbida@demo.smartlink.cm',
            'provider' => 'cyrille.kamdem@demo.smartlink.cm',
            'status' => 'in_progress',
            'days_ago' => 5,
            'message' => 'Nous démarrons une construction à Tamdja et cherchons un maçon pour '
                .'l\'élévation. Environ 90 mètres carrés de murs.',
            'reply' => 'Je peux passer faire le métré cette semaine. Avez-vous déjà les plans '
                .'et le terrain borné ?',
        ],
        [
            'client' => 'sandrine.enow@demo.smartlink.cm',
            'provider' => 'armand.nkeng@demo.smartlink.cm',
            'status' => 'accepted',
            'days_ago' => 2,
            'message' => 'Mon ordinateur portable ne démarre plus depuis la dernière coupure. '
                .'L\'écran reste noir mais le ventilateur tourne.',
            'reply' => 'Ça ressemble à une alimentation ou une barrette de mémoire. Apportez-le, '
                .'le diagnostic est gratuit et je vous dis tout de suite.',
        ],
        [
            'client' => 'boubakary.hamadou@demo.smartlink.cm',
            'provider' => 'ibrahim.sali@demo.smartlink.cm',
            'status' => 'accepted',
            'days_ago' => 1,
            'message' => 'Bonjour, je voudrais une livraison de dix bidons chaque lundi matin. '
                .'C\'est possible en abonnement ?',
            'reply' => 'Oui, tournée du lundi entre 6h et 8h. Je vous mets sur la liste dès '
                .'lundi prochain.',
        ],
        [
            'client' => 'aissatou.bello@demo.smartlink.cm',
            'provider' => 'rodrigue.fotso@demo.smartlink.cm',
            'status' => 'viewed',
            'days_ago' => 1,
            'message' => 'Le climatiseur de la chambre souffle de l\'air tiède depuis une semaine. '
                .'Il a trois ans et n\'a jamais été entretenu.',
        ],
        [
            'client' => 'herve.manga@demo.smartlink.cm',
            'provider' => 'marthe.abena@demo.smartlink.cm',
            'status' => 'sent',
            'days_ago' => 0,
            'message' => 'Bonjour, nous organisons un baptême le mois prochain pour environ '
                .'quatre-vingts personnes. Pouvez-vous me faire une proposition de menu ?',
        ],
        [
            'client' => 'olivier.beyala@demo.smartlink.cm',
            'provider' => 'sylvie.atangana@demo.smartlink.cm',
            'status' => 'sent',
            'days_ago' => 0,
            'message' => 'Mon fils est en terminale C et décroche en maths. Nous cherchons deux '
                .'séances par semaine jusqu\'au bac.',
        ],
        [
            'client' => 'nadege.tsafack@demo.smartlink.cm',
            'provider' => 'didier.owona@demo.smartlink.cm',
            'status' => 'refused',
            'days_ago' => 15,
            'message' => 'Nous nous marions le 12 du mois prochain à Kribi. Êtes-vous disponible '
                .'et vous déplacez-vous hors de Douala ?',
            'reply' => 'Merci de votre message, mais je suis déjà pris ce week-end-là. '
                .'Je peux vous recommander un confrère si vous voulez.',
        ],
        [
            'client' => 'thierry.njoya@demo.smartlink.cm',
            'provider' => 'estelle.nguema@demo.smartlink.cm',
            'status' => 'cancelled',
            'days_ago' => 18,
            'message' => 'Je cherche une chemise en pagne sur mesure pour une cérémonie.',
        ],
    ],
];
