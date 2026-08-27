# Le brief à coller dans Google Stitch

Ce dossier contient 29 maquettes déjà générées, toutes reprises dans
`resources/views`. Ce fichier est la consigne qui les a produites, remise au
propre — pour qu'un **nouvel** écran revienne au même design system plutôt
qu'au style générique par défaut de l'outil.

## Mode d'emploi

Stitch travaille écran par écran. Deux blocs, donc :

1. Le **socle** ci-dessous décrit le produit et la charte. Collez-le en tête
   de chaque demande — la version courte suffit une fois que la conversation
   a déjà produit un écran cohérent.
2. Le **bloc écran** décrit l'écran voulu. Un seul écran par demande : deux
   écrans dans la même consigne et Stitch fusionne les deux en une page.

Les valeurs de couleur, de police et de rayon ci-dessous sont celles du
projet, relevées dans `DESIGN.md` et dans le `tailwind.config` des maquettes
existantes. Ne les paraphrasez pas : un « vert foncé » rend un vert qui n'est
pas le nôtre, et la reprise en Blade coûte alors une passe de correction.

---

## Bloc socle — version complète

> **Produit.** SmartLink met en relation des clients et des prestataires de
> services de proximité au Cameroun : plomberie, électricité, coiffure,
> ménage, maçonnerie, couture, traiteur, cours particuliers, mécanique.
> Interface en français.
>
> **Modèle économique — contrainte absolue.** Un seul flux d'argent existe :
> du prestataire vers SmartLink, sous forme d'un abonnement mensuel réglé en
> Mobile Money, après trente jours d'essai gratuit. Aucune somme ne transite
> entre client et prestataire. Ne dessine donc **jamais** de panier, de
> paiement en ligne d'une prestation, d'acompte, de facture de prestation, de
> commission, ni de bouton « Payer » côté client. Les prix affichés sur les
> services sont indicatifs ; le règlement se convient directement entre les
> deux parties, hors plateforme.
>
> **Public et matériel.** Un prestataire consulte l'écran entre deux
> interventions, sur un téléphone Android de milieu de gamme, en 3G. Conçois
> pour 390 px de large d'abord, puis élargis. Lisibilité immédiate et clarté
> opérationnelle avant l'expression.
>
> **Palette** (jetons Material 3, valeurs exactes) :
> primary `#005538`, on-primary `#ffffff`, primary-container `#0f6f4c`,
> on-primary-container `#9aefc3`, secondary-container `#aff1cf`,
> on-secondary-container `#317054`, tertiary `#7b3500`,
> tertiary-container `#a04700`, error `#ba1a1a`, error-container `#ffdad6`,
> background et surface `#f9faf7`, on-surface `#191c1b`,
> on-surface-variant `#3f4943`, surface-container-lowest `#ffffff`,
> surface-container-low `#f3f4f1`, surface-container `#edeeeb`,
> surface-container-high `#e7e8e6`, outline `#6f7a72`,
> outline-variant `#bec9c0`.
>
> Le vert porte l'action et ce qui compte, jamais la décoration. L'ambre est
> réservé aux statuts professionnels et aux avertissements — abonnement qui
> expire, plafond atteint, demande en attente — et n'apparaît jamais sur le
> même écran que le vert comme second accent. Le rouge ne sert qu'aux erreurs
> et aux actions destructives.
>
> **Typographie** — trois familles, trois rôles :
> Hanken Grotesk pour les titres (headline-xl 36/44 poids 800 interlettrage
> −0.02em ; headline-lg 28/34 poids 700 ; headline-md 22/28 poids 600) ;
> Source Sans 3 pour le corps (body-lg 18/26 ; body-md 16/24 ; libellés de
> boutons 16/24 poids 600) ; **JetBrains Mono pour toute donnée chiffrée** —
> prix, dates, références, compteurs, notes — en 14/20 poids 500.
>
> **Formes et profondeur.** Cartes, champs et conteneurs en rayon 12 px.
> Boutons, pastilles et onglets en pilule complète. La profondeur vient de
> **bordures de 1 px `#bec9c0`**, pas d'ombres : seules les modales et les
> menus contextuels en portent une. Rien ne se soulève au survol — l'état
> survolé ou sélectionné se marque par un changement de fond.
>
> **Mise en page.** Marges latérales 16 px sur mobile, 32 px à partir de
> 768 px. Conteneur centré à 1 200 px au maximum. Rythme vertical de 24 à
> 32 px entre blocs, grille de base 4 px. Zones tactiles : 16 px de
> rembourrage minimum, 56 px de hauteur de ligne dans les listes.
>
> **Navigation — trois formes, une par plage de largeur.** Sous 768 px, une
> barre d'onglets basse de cinq entrées au maximum. Entre 768 et 1 279 px,
> une barre horizontale haute. À partir de 1 280 px, un tiroir latéral fixe
> de 320 px à gauche qui remplace la barre haute — jamais les deux ensemble.
>
> **Écriture.** Français, vouvoiement, phrases courtes. Les termes techniques
> français sont plus longs que leurs équivalents anglais : tout libellé de
> bouton ou d'onglet doit tenir à 390 px. Aucun texte de remplissage latin —
> écris le vrai contenu camerounais : Douala, Akwa, Bonanjo, Bonapriso,
> Deido, Bonamoussadi, Yaoundé, Bafoussam, Garoua ; des montants en FCFA ;
> des noms comme Aïcha Mballa, Jean-Paul Eto'o.

## Bloc socle — version courte

À employer une fois que la conversation Stitch a déjà rendu un écran conforme.

> Application SmartLink : mise en relation clients / prestataires de services
> au Cameroun, en français, mobile d'abord à 390 px. Aucun paiement de
> prestation dans l'interface — le seul flux d'argent est l'abonnement du
> prestataire. Vert `#005538` pour l'action, ambre `#7b3500` pour les
> avertissements seuls, rouge `#ba1a1a` pour les erreurs, fond `#f9faf7`,
> blanc `#ffffff` pour les listes, bordures 1 px `#bec9c0` et **aucune
> ombre**. Titres Hanken Grotesk, corps Source Sans 3, **tous les chiffres en
> JetBrains Mono**. Cartes en rayon 12 px, boutons en pilule. Contenu réel
> camerounais, montants en FCFA, jamais de lorem ipsum.

---

## Bloc écran

Complétez les cinq lignes, à la suite du socle :

```
Écran : <nom>
Rôle qui le voit : client | prestataire | administrateur | visiteur
Ce que la personne vient y faire : <une phrase, à l'infinitif>
Contenu : <les blocs, du haut vers le bas>
État à montrer : rempli | vide | en erreur | chargement
```

Exemple, tel qu'il a servi :

```
Écran : Signaler un litige
Rôle qui le voit : client
Ce que la personne vient y faire : décrire un problème sur une prestation
  terminée et joindre une preuve.
Contenu : en-tête avec retour ; rappel de la demande concernée (titre du
  service, prestataire, date) dans une carte ; choix du motif en liste de
  pastilles ; zone de texte « Que s'est-il passé ? » ; dépôt de fichiers
  facultatif avec la liste des fichiers joints ; mention que l'équipe
  répond sous 48 h ; bouton d'envoi pleine largeur en bas.
État à montrer : rempli
```

**Demandez toujours l'état vide en plus de l'état rempli.** C'est celui que
Stitch omet par défaut et celui que voit un compte neuf — donc le premier
écran de la vie d'un utilisateur.

---

## Ce qu'il ne faut pas demander

- **Un écran déjà fait.** Les 29 dossiers voisins couvrent tout le produit.
  Regardez d'abord si le vôtre y est.
- **Plusieurs écrans dans une seule consigne.** Stitch les fusionne.
- **Un mode sombre.** Le projet n'en a pas ; une maquette sombre serait à
  retraduire entièrement.
- **Des icônes hors Material Symbols.** Le projet ne charge que ce jeu, et
  seulement les ligatures listées dans `config/icons.php`
  (`php artisan icons:sync` tient la liste à jour). Une icône inventée
  s'affiche en toutes lettres au milieu de la page.
- **Des photographies dans la maquette.** Les couvertures sont générées
  (`database/seeders/data/images/`) ou déposées via `design/photos/`.

## Après la génération

Déposez l'export dans `design/stitch-imports/<nom_de_l_écran>/`, avec son
`code.html` et son `screen.png`, comme les autres. La reprise en Blade suit —
les maquettes font foi pour la mise en page, jamais pour le contenu, qui vient
du modèle de données.
