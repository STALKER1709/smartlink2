---
name: SmartLink
description: Place de marché de services de proximité au Cameroun — mise en relation, jamais transaction.
colors:
  primary: "#005f48"
  on-primary: "#ffffff"
  primary-container: "#007a5e"
  on-primary-container: "#a4ffdd"
  secondary: "#805600"
  on-secondary: "#ffffff"
  secondary-container: "#feb637"
  on-secondary-container: "#6e4900"
  tertiary: "#aa001a"
  on-tertiary: "#ffffff"
  tertiary-container: "#d41829"
  on-tertiary-container: "#ffe8e6"
  error: "#ba1a1a"
  on-error: "#ffffff"
  error-container: "#ffdad6"
  on-error-container: "#93000a"
  surface: "#f9f9fc"
  surface-container-lowest: "#ffffff"
  surface-container-low: "#f3f3f6"
  surface-container: "#eeeef0"
  surface-container-high: "#e8e8ea"
  on-surface: "#1a1c1e"
  on-surface-variant: "#3e4944"
  inverse-surface: "#2f3133"
  inverse-on-surface: "#f0f0f3"
  outline: "#6e7a74"
  outline-variant: "#bdc9c2"
typography:
  display-lg:
    fontFamily: "Lexend, ui-sans-serif, system-ui, sans-serif"
    fontSize: "48px"
    fontWeight: 700
    lineHeight: "56px"
    letterSpacing: "-0.02em"
  headline-lg:
    fontFamily: "Lexend, ui-sans-serif, system-ui, sans-serif"
    fontSize: "32px"
    fontWeight: 600
    lineHeight: "40px"
  headline-md:
    fontFamily: "Lexend, ui-sans-serif, system-ui, sans-serif"
    fontSize: "24px"
    fontWeight: 600
    lineHeight: "32px"
  headline-sm:
    fontFamily: "Lexend, ui-sans-serif, system-ui, sans-serif"
    fontSize: "20px"
    fontWeight: 600
    lineHeight: "28px"
  body-lg:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "18px"
    fontWeight: 400
    lineHeight: "28px"
  body-md:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "16px"
    fontWeight: 400
    lineHeight: "24px"
  label-md:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "14px"
    fontWeight: 600
    lineHeight: "20px"
    letterSpacing: "0.01em"
  label-sm:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "12px"
    fontWeight: 500
    lineHeight: "16px"
  button-text:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "16px"
    fontWeight: 600
    lineHeight: "24px"
  label-numeric:
    fontFamily: "JetBrains Mono, ui-monospace, monospace"
    fontSize: "14px"
    fontWeight: 500
    lineHeight: "20px"
rounded:
  sm: "0.125rem"
  DEFAULT: "0.25rem"
  md: "0.375rem"
  lg: "0.5rem"
  xl: "0.75rem"
  full: "9999px"
spacing:
  unit: "8px"
  gutter: "24px"
  margin-mobile: "16px"
  margin-desktop: "64px"
  container-max-width: "1280px"
elevation:
  elevation-1: "0 4px 12px rgba(26, 28, 30, 0.12)"
  elevation-2: "0 8px 24px rgba(26, 28, 30, 0.16)"
  overlay: "0 12px 32px rgba(26, 28, 30, 0.20)"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    typography: "{typography.button-text}"
    rounded: "{rounded.full}"
    padding: "10px 20px"
  button-primary-hover:
    backgroundColor: "{colors.primary-container}"
    textColor: "{colors.on-primary}"
  button-secondary:
    backgroundColor: "{colors.surface-container-lowest}"
    textColor: "{colors.primary}"
    typography: "{typography.button-text}"
    rounded: "{rounded.full}"
    padding: "10px 20px"
  card:
    backgroundColor: "{colors.surface-container-lowest}"
    textColor: "{colors.on-surface}"
    rounded: "{rounded.xl}"
    padding: "16px"
  badge:
    backgroundColor: "{colors.secondary-container}"
    textColor: "{colors.primary}"
    rounded: "{rounded.full}"
    padding: "2px 10px"
  input:
    backgroundColor: "{colors.surface-container-lowest}"
    textColor: "{colors.on-surface}"
    typography: "{typography.body-md}"
    rounded: "{rounded.lg}"
    padding: "10px 14px"
  price:
    textColor: "{colors.on-surface}"
    typography: "{typography.label-numeric}"
---

## Overview

**Minimalisme pragmatique.** L'interface sert un prestataire qui la consulte entre deux
interventions, sur un Android de milieu de gamme, en 3G. Elle privilégie la lisibilité
immédiate et la clarté opérationnelle sur l'expression. La confiance se gagne par la
structure et la précision, jamais par l'effet.

Le vert profond est l'ancre. Il ne décore pas : il signale ce sur quoi on agit et ce qui
compte. Partout ailleurs, du blanc sur un fond très légèrement grisé, et des bordures
d'un pixel pour découper l'espace.

Mode dominant : **Operate**. Les pages publiques — accueil, recherche, fiche service,
annuaire — basculent en **Persuade** : elles doivent convaincre un visiteur qui ne
connaît pas SmartLink.

## La charte, et d'où elle vient

La charte du produit est **« SmartLink »**, la charte Google Stitch du projet
`3364420177367452718` (`assets/cc5a3e92d2b241d2b97d469fc098f9f4`, 26 écrans).
Ce document-ci reste celui qui fait foi pour le code ; il en énonce les règles
et les quelques écarts tenus volontairement.

Elle a remplacé une charte antérieure, « SmartLink Core » (projet
`7168677943593520780`), dont l'historique Git garde la trace. Ce n'était pas un
réglage : vert profond contre vert plus clair, vert clair contre **jaune**,
ambre contre **rouge**, Hanken Grotesk et Source Sans 3 contre **Lexend et
Inter**, et surtout des bordures contre des **ombres**. Le motif de la bascule
est l'identité : vert, jaune, rouge sont les couleurs du drapeau camerounais,
et une place de marché camerounaise gagne à les porter.

**Ce qui n'a pas basculé, et pourquoi :**

- **JetBrains Mono pour les chiffres.** La charte amont n'a pas de police à
  chasse fixe, et n'a rien pour remplir ce rôle : aligner les colonnes de
  montants dans une liste, distinguer au premier coup d'œil une donnée d'une
  phrase. C'est fonctionnel, pas décoratif.
- **Les marges de téléphone.** La charte ne décrit que le bureau et pose 64 px
  de marge ; sa maquette d'accueil les applique **sans le moindre palier**. Sur
  un écran de 390 px, 128 px de marges ne laissent que 262 px de contenu. Le
  téléphone garde ses 16 px, et 64 px commencent à `md`.
- **La translation au survol.** Sa maquette ajoute `hover:-translate-y-1` à
  l'ombre — ce que sa propre charte ne demande nulle part : elle écrit que le
  soulèvement se *simule* par une ombre accrue. C'est cette lecture qui est
  retenue, et la raison tient : un bloc qui se déplace emmène avec lui ce que
  l'œil suivait, et répété sur une liste il rend la page instable.
- **Le rayon de 12 px.** La charte annonce `xl: 1.5rem` dans son texte, mais la
  configuration Tailwind que son propre code génère écrit `xl: 0.75rem`. Les
  deux ne concordent pas ; c'est le code qui a été suivi, et il donne 12 px.

⚠️ Cette charte se contredit sur un dernier point : son frontmatter donne
`primary: #005f48` quand sa couleur de réglage — celle que l'auteur a posée —
donne `#007a5e`. Les deux sont retenues, la première comme `primary` et la
seconde comme `primary-container` : c'est ainsi que son propre code les emploie.


## Colors

Les trois couleurs du drapeau, et chacune a un emploi, un seul.

- **Vert `#005f48`** : actions clés, chiffres qui comptent, état actif, et le
  badge « Vérifié » — le signal de confiance de la plateforme. Sa variante
  `primary-container #007a5e` sert le survol et l'appui.
- **Jaune `secondary-container #feb637`** : les **notes** et les mises en avant.
  C'est le gain le plus net de la bascule : la note d'un prestataire et un
  abonnement qui expire portaient jusqu'ici la même couleur ambre, faute d'en
  avoir deux. Le texte posé dessus est `on-secondary-container #6e4900` — un
  brun foncé, parce que du blanc sur ce jaune ne se lit pas.
- **Rouge `tertiary-container #d41829`** : alertes critiques et statuts urgents.
  Il ne décore jamais, et il ne se dispute pas l'écran avec `error` — celui-ci
  reste réservé aux erreurs de formulaire et aux actions destructives.
- **Surfaces** : le fond de page `#f9f9fc` porte les contrôles, les cartes
  reposent sur du blanc. Cette différence d'un ton, doublée d'une ombre légère,
  sépare ce sur quoi on agit de ce qu'on lit.

Le jaune et le rouge ne paraissent jamais ensemble comme accents concurrents.


## Typography

Deux intentions, deux familles.

- **Lexend** pour les titres. Sa clarté géométrique reste lisible sur un écran de
  milieu de gamme et pour un lecteur âgé — c'est la raison que la charte amont
  en donne, et elle vaut ici plus qu'ailleurs.
- **Inter** pour le corps et les étiquettes. Sa régularité tient les vues denses :
  listes de services, fiches de prestataires, tableaux de montants.
- **JetBrains Mono** pour **toute donnée chiffrée** : prix, dates, références,
  compteurs. La chasse fixe aligne les colonnes de montants dans une liste, et distingue
  au premier coup d'œil une donnée d'une phrase.

Quatre paliers de titre, et pas un de plus : `display-lg` 48/56 poids 700,
`headline-lg` 32/40 poids 600, `headline-md` 24/32 poids 600, `headline-sm` 20/28
poids 600. Le dernier est le titre d'une carte ou d'une rangée de liste. **La
charte amont ne le nomme pas** — et la maquette de son accueil est allée chercher
`text-[20px]` six fois de suite, faute de l'avoir. Il est nommé ici, à la taille
qu'elle a réinventée à chaque fois. Un titre écrit avec une taille hors de cette
table est un palier de plus que personne n'a décidé.

**Deux paliers d'étiquette**, `label-md` 14/20 poids 600 et `label-sm` 12/16 poids
500. Une étiquette est ce qui nomme une donnée sans être une phrase : intitulé de
champ, libellé d'onglet, métier en capitales au-dessus d'un titre, texte d'une
pastille. Ils manquaient, et la même étiquette s'écrivait de neuf façons au fil des
vues — `text-xs`, `text-sm`, `text-[10px]`, `text-[11px]`, `text-[12px]`,
`text-[13px]`, trois d'entre elles en JetBrains Mono, sur des mots.

**Un chiffre prend un palier de titre, en JetBrains Mono.** Il n'y a pas d'échelle
séparée pour les nombres : un prix mis en avant, un compteur de tuile, un montant
d'abonnement se composent en `text-headline-lg` ou `text-display-lg` selon leur
poids dans la page. Sans cette règle, cinq corps circulaient pour la même chose —
20, 24, 30, 36 et 48 px — chacun choisi dans sa vue.

**Aucune taille ne s'écrit hors de cette table**, ni dans l'échelle générique de
Tailwind (`text-sm`, `text-2xl`), ni en pixels (`text-[13px]`). Les deux échelles
rendent les mêmes pixels sur les premiers paliers, et c'est bien le danger : la
dérive ne se voit pas. Seules les icônes en sont exemptes — Material Symbols prend
sa dimension en `font-size`, et une icône n'est pas du texte. `CharteTest` tient les
trois règles.

Les termes techniques français sont plus longs que leurs équivalents anglais. Les
libellés de boutons et d'onglets doivent être éprouvés à 390 px avant d'être adoptés, pas
après.

## Layout

- **Mobile d'abord, 390 px comme largeur de référence.** Marges latérales de 16 px,
  64 px à partir de `md`. La charte amont ne décrit que le bureau et pose ces
  64 px partout : sa maquette d'accueil les applique sans le moindre palier, ce
  qui ne laisse que 262 px de contenu sur un téléphone.
- **Palier `xs` à 480 px.** Le premier palier de Tailwind est à 640 px, ce qui laisse
  tous les téléphones du mauvais côté. En dessous de `xs`, les grilles passent à une
  colonne et les cartes de service adoptent une disposition horizontale.
- **Conteneur** centré à 1 280 px au maximum.
- **Rythme vertical** de 24 à 32 px entre blocs. Grille de base de 8 px,
  gouttière de 24 px.
- **Deux formes de navigation, une par plage de largeur.** Barre d'onglets basse
  sous 768 px (`md:hidden`), cinq entrées au maximum — le contenu réserve `4.75rem`
  en bas pour ne pas passer dessous. Barre horizontale au-dessus.
- **Pas de tiroir latéral de bureau.** Il a existé, à partir de 1 280 px, et il a été
  retiré. La raison de son seuil vaut d'être gardée : un tiroir fixé rétrécit le
  contenu sans que les requêtes de média le sachent — elles mesurent la fenêtre, pas
  la place qui reste. À 1 024 px, `lg:grid-cols-6` s'appliquait à 704 px de large et
  « Prestataires » se lisait « Prestatair… ».
- **Une seule table de destinations** (`App\Support\NavigationLinks`), lue par les
  deux formes : `principaux()` pour la boucle quotidienne du rôle, `secondaires()`
  pour le menu du compte, `compte()` pour l'onglet de profil. Recopiée, elle dérive au
  premier écran ajouté d'un seul côté.
- **Zones tactiles** : 16 px de rembourrage interne minimum, 56 px de hauteur de ligne
  dans les listes.

## Elevation & Depth

La profondeur vient de **couches tonales et de trois paliers d'ombre**. C'est ce
qui a changé le plus profondément : la charte précédente la tirait des bordures
d'un pixel et refusait toute ombre.

- **`elevation-1`** `0 4px 12px` à 12 % — les cartes, les boutons flottants.
  Une carte garde en plus sa bordure d'un pixel `outline-variant` : l'ombre
  donne la profondeur, la bordure donne le bord net dont un écran de milieu de
  gamme a besoin.
- **`elevation-2`** `0 8px 24px` à 16 % — l'état survolé ou actif. Le
  soulèvement se lit **dans l'ombre seule**.
- **`overlay`** `0 12px 32px` à 20 % — modales, menus, panneau de l'assistant :
  ce qui passe au-dessus d'un contenu dont on ignore la couleur.

**Rien ne se déplace au survol.** L'ombre monte d'un palier, la géométrie ne
bouge pas — ni `scale`, ni `translate-y`. `CharteTest` refuse les deux, et
refuse aussi toute ombre écrite hors de ces trois paliers : la maquette amont
écrivait six fois `shadow-[0_8px_24px_rgba(0,0,0,0.12)]` en clair, à deux
opacités différentes pour le même rôle.

**Les champs de saisie ne portent pas d'ombre.** Une bordure d'un pixel qui
s'épaissit et verdit au focus, rien de plus. Treize d'entre eux traînaient un
`shadow-sm` venu du gabarit Breeze, que personne n'avait décidé.


## Shapes

- **Cartes, champs, conteneurs** : `rounded-xl` (12 px).
- **Boutons, pastilles, onglets** : `rounded-full`. Le contraste entre le
  rectangle arrondi des structures et la pilule des actions rend les zones
  cliquables lisibles sans couleur supplémentaire.
- **Vignettes d'illustration** : `rounded-lg`, carrées dans les rangées de liste.
- **Rangées de liste** : aucun rayon. Un filet les sépare, rien ne les encadre.
- **Une seule exception au 12 px** : les bulles de conversation, en
  `rounded-2xl` (16 px). Une bulle n'est pas une carte — c'est la forme qui la
  distingue du reste de la page. Partout ailleurs, `rounded-2xl` sur un
  conteneur est une dérive : `CharteTest` la refuse.


## Components

> **Ce document fait foi pour la mise en page des écrans.** Les maquettes
> Google Stitch qui ont servi à la refonte ont été retirées du dépôt une fois
> l'intégration faite et vérifiée à l'écran ; l'historique Git les conserve, et
> le projet amont (`7168677943593520780`) reste consultable — voir « La charte
> amont » plus haut pour ce qui en est retenu et ce qui en diverge.
> Elles n'avaient de toute façon pas été éprouvées à 390 px : là où une
> maquette laissait un titre et son lien côte à côte, ou un bouton comprimer
> son libellé, la règle appliquée est celle énoncée ici — l'élément secondaire
> passe sous le principal.

- **Rangée de prestataire** — même régime que la rangée de service : métier en
  capitales, nom, ville et nombre de services, note. C'était une carte bordée
  répétée cinq fois sur l'accueil et vingt fois dans l'annuaire, à cinq cents
  pixels l'une — deux motifs pour le même travail sur un seul écran, celui du
  bas étant déjà une liste.
- **Titre de section** (`x-section-header`) — sur mobile, le lien « Voir tout »
  passe **sous** le titre. Posé à côté, il tenait la ligne de base de la
  deuxième ligne d'un titre qui passe presque toujours à la ligne à 390 px.
- **Rangée de service — une liste, pas une carte.** Un annuaire se parcourt de haut en
  bas : vignette carrée à gauche, métier en capitales, titre, prestataire et lieu, prix
  en JetBrains Mono. Les rangées se séparent par un filet et **ne portent aucune boîte**.
  Deux colonnes seulement à partir de `lg`, et ce sont toujours des rangées.

  Le filet appartient à la rangée, jamais au conteneur : `divide-y` de Tailwind remet
  `border-bottom` à zéro sur tous les enfants **sauf le premier**, ce qui ne laisse qu'une
  seule ligne visible dès qu'on passe en deux colonnes.
- **Rangée de chiffres** — les colonnes se séparent par un **écart d'un pixel** sur
  le fond du conteneur, jamais par `divide-x` : dans une grille, celui-ci pose une
  bordure à gauche de tout enfant sauf le premier, ce qui met un trait contre le bord
  gauche dès la deuxième rangée et n'en met aucun *entre* les rangées. L'écart ne
  connaît ni index ni palier. La grille déborde latéralement (`-mx`) pour que le
  rembourrage de la première colonne retombe sur la marge de la page et que le premier
  chiffre s'aligne avec le titre. Corollaire : une rangée incomplète laisse voir le
  fond en aplat — le nombre de tuiles doit être un multiple du nombre de colonnes à
  chaque palier.
- **Colonne de statistique** — chiffre, libellé, indication, séparés par un filet
  vertical. Ni boîte, ni icône décorative : quatre pictogrammes alignés n'apprennent rien
  que le libellé ne dise déjà. La ligne d'indication est **toujours réservée**, même vide :
  les colonnes d'une rangée s'étirent à la hauteur de la plus haute, et sans cette réserve
  les chiffres cessent de s'aligner.
- **État vide** — aligné à gauche, sans cadre en pointillés ni grande icône grise. C'est
  une phrase adressée au visiteur, pas un panneau d'absence.
- **Bouton d'une action soumise à autorisation** — il interroge la Policy, pas
  une seconde vérité : un bouton offert au-delà du plafond mène à une page 403
  nue, au moment précis où l'utilisateur en a le plus besoin. Au plafond il ne
  disparaît pas — un prestataire qui ne trouve plus « Publier » croit à une
  panne — il change de libellé, passe au rouge, dit ce qui bloque et mène là
  où cela se débloque.
- **Fil de discussion** — la zone de saisie **colle** au bas de l'écran
  (`sticky bottom-[4.75rem]`), au-dessus de la barre d'onglets ; elle ne se
  calcule pas en `dvh`. Une hauteur calculée doit deviner celle de l'en-tête,
  qui change avec le bouton d'action : un `calc(100dvh - 16rem)` posait la
  saisie **sous** la barre d'onglets, mesure à l'appui. Sur un écran de
  conversation, ni pied de page ni bulle d'assistant — celle-ci est ronde et
  verte comme le bouton d'envoi, et se pose à côté de lui.
- **Liste de messagerie** — chaque rangée porte l'**aperçu du dernier message**
  et le nombre de non-lus. Sans eux, il faut ouvrir chaque fil pour savoir
  lequel attend une réponse.
- **Choix d'une note** — des étoiles, jamais une liste déroulante. C'est le
  seul moment où un client s'exprime sur la plateforme. Des boutons radio
  masqués (`peer` + `flex-row-reverse`) : cliquable et accessible sans
  JavaScript, et les étoiles précédentes se teintent avec la sélection.
- **Filtre** — ne proposer que les valeurs présentes, avec leur nombre. Un
  filtre qui rend zéro n'est pas un filtre, c'est un piège ; neuf pastilles
  sur deux rangées avant le premier résultat en sont neuf.
- **Champ de fichier** — jamais nu. `<input type="file">` laissé tel quel
  affiche « Choose File · No file chosen », en anglais et au gabarit du
  système, au milieu d'un formulaire français dessiné : c'est l'élément qui
  trahit le plus l'inachevé. `x-file-input` l'habille et affiche le nom du
  fichier choisi.
- **Vignette** — le repli se pose **sous** l'image, jamais dans un `@else` :
  une branche « pas d'image en base » ne se déclenche pas quand la ligne existe
  mais que le fichier a disparu, et le navigateur affiche alors sa propre icône
  d'image cassée. `onerror` retire l'image et découvre le repli.
- **Boutons** — rembourrage horizontal généreux : un libellé français touche les bords
  d'un bouton dimensionné pour l'anglais. Primaire vert plein ; secondaire blanc à
  bordure verte.
- **Champs de saisie** — étiquette toujours visible au-dessus du champ, jamais en
  substitut à l'intérieur. Bordure grise qui passe au vert au focus.
- **Pastilles de statut** — fond coloré à faible opacité, texte en couleur pleine.
  Dans une liste où l'état normal est l'immense majorité, **seule l'exception porte
  une pastille** : « Actif » répété sur cinquante lignes n'apprend rien et noie le
  compte suspendu.
- **Rangée d'administration** — une boîte autour de la liste entière, jamais une par
  ligne, et **les actions à droite de la rangée**, jamais empilées sous le contenu :
  en dessous, un mot rouge s'intercale entre deux noms à chaque ligne. Deux actions
  qui forment une paire restent voisines — `ml-auto` les envoie aux deux bords
  opposés de l'écran, à trois cents pixels l'une de l'autre.
- **Barre d'onglets basse** — icône dans une pastille verte quand l'onglet est actif,
  libellé court dessous. Les libellés diffèrent de ceux de la barre large : « Accueil »
  et non « Tableau de bord ».
- **Illustrations de couverture** — quatorze motifs par métier, produits à partir de
  cette palette et posés dans une **scène** : ciel avec sa lumière basse, ligne
  d'horizon, sol, feuillages au premier plan, silhouette au travail. Seuls, les
  motifs flottaient sur un dégradé et se lisaient comme des pictogrammes de
  remplacement — ce qu'ils étaient. Une catégorie sans motif retombe sur l'icône
  du métier, jamais sur un rectangle gris.

  Pas d'ombre de contact : les motifs ne posent pas tous au sol — un tuyau et
  ses gouttes se lisent en l'air, une tête de coiffure se lit assise — et une
  ombre sous un objet qui flotte est pire que pas d'ombre du tout.

  **Ces dessins sont un repli.** Dès qu'une photographie existe pour une
  catégorie, elle les remplace : `design/photos/` puis `php artisan
  photos:import`. Une photographie doit être camerounaise et de droits vérifiés
  — une photo de stock d'un plombier européen dessert une place de marché
  camerounaise, et une image trouvée en ligne pose une question de droits que
  personne ne veut découvrir après la mise en ligne.

## Do's and Don'ts

**À faire**

- Écrire les montants avec l'espace insécable des milliers : « 5 000 FCFA », en
  JetBrains Mono.
- Énoncer clairement que SmartLink ne perçoit aucun paiement entre client et
  prestataire, partout où un prix apparaît.
- Éprouver toute mise en page à 390 px **avant** de la juger sur un écran large.
- Réserver le jaune aux notes et aux mises en avant, le rouge aux alertes.
- Faire porter les états vides par une action : ce qu'il faut faire, pas ce qui manque.

**À ne pas faire**

- **Faire d'une boîte le motif de mise en page.** Une carte blanche à coin arrondi,
  bordée d'un pixel, posée sur du gris et répétée quinze fois ne hiérarchise rien : chaque
  élément pèse exactement autant que le suivant. C'est la signature d'une interface
  produite sans intention. Une liste séparée par des filets dit la même chose, en plus
  dense et en plus rapide.
- **Emboîter une carte dans une carte.** Jamais.
- **Déplacer un élément au survol.** L'ombre monte d'un palier — c'est elle qui
  porte le soulèvement. Ni `scale`, ni `translate-y` : un bloc qui bouge emmène
  avec lui ce que l'œil suivait.
- **Écrire une ombre hors des trois paliers.** `shadow-md`, ou une valeur en
  clair dans des crochets, est une profondeur que personne n'a décidée.
- Employer une couleur pour décorer.
- Placer une action principale dans la barre de titre d'une section à laquelle elle
  n'appartient pas — sur mobile elle passe à la ligne et paraît agir sur ce titre.
- Répéter la même information dans deux tuiles voisines.
- Laisser un libellé se tronquer : le raccourcir à la source plutôt que de le couper à
  l'affichage.
- **Réduire une action à son pictogramme sur mobile.** « Modifier » et
  « Supprimer » ramenés à deux icônes ne se distinguaient que par la couleur,
  avec un `title=` qu'aucun doigt n'ouvre. Si la place manque, l'action descend
  sur sa propre ligne — elle ne perd pas son nom.
- **Composer un mot au corps d'un grand chiffre.** « Essentiel » dans une tuile
  de statistique écrasait les « 3 », « 1 » et « 2 » de la même rangée : la
  colonne la moins chiffrée devenait la plus voyante.
- **Écrire deux fois la même donnée côte à côte.** « 4,0 » en grand suivi de
  « 4,0 (2 avis) » : les étoiles ne portent que le décompte.
- **Montrer une donnée technique nue.** Un identifiant interne, une paire de
  coordonnées : si elle doit paraître, elle est nommée et subordonnée à ce que
  l'utilisateur vient lire. « Demande n° 28 » en titre de page : le client
  reconnaît sa demande à la prestation, pas à son numéro.
- **Laisser une valeur de colonne atteindre l'écran.** « Le statut de votre
  demande est passé à "in_progress" » dans une notification, « Lundi_vendredi »
  dans les horaires d'un prestataire : les libellés viennent de
  `App\Support\RequestStatus` ou sont mis en forme par la vue, jamais repris
  bruts de la base. Une phrase composée à l'écriture se fige — la vue doit
  pouvoir la recomposer depuis les données, sans quoi l'historique garde
  l'ancienne formulation.
- **Laisser deux copies d'un même contrôle vivre côte à côte.** Le champ de
  recherche existait à l'accueil et sur la liste des services ; celui de
  l'accueil avait déjà pris l'alignement centré de son bloc parent, pas celui
  de la liste. Un contrôle, un composant.
- **Offrir un formulaire qui ne peut pas aboutir.** Sans destinataire, la
  demande est refusée par la validation : le client recevait « Le champ
  service est obligatoire lorsque provider id n'est pas présent » — deux
  colonnes nommées à propos de deux champs qu'il n'a jamais vus. On demande
  d'abord ce qui manque, on n'accuse pas après coup.
- **Recadrer un document qu'on demande de vérifier.** `object-cover` sur une
  pièce d'identité en montre la bande centrale, où l'on ne voit ni le type de
  document ni la photo.
- **Donner à une variable interne d'un composant le nom d'une de ses
  propriétés.** L'affectation écrase silencieusement la valeur reçue, et la
  propriété n'a plus aucun effet.
- **Poser un sommaire au-dessus de ce qu'il annonce.** Quatre pavés menant aux
  quatre sections immédiatement en dessous, dont ils répètent les titres : neuf
  questions ne se naviguent pas, elles se lisent.
- **Reléguer ce qu'on est venu voir derrière ce qui lui ressemble.** Sur la
  fiche d'un service, « Services similaires » vivait dans la colonne
  principale, donc au-dessus de la colonne d'action sur mobile : on lisait la
  description, puis quatre annonces concurrentes, et le prestataire de *ce*
  service venait après.
- **Laisser un numéro en texte mort.** Sur un téléphone, appeler est l'action
  principale d'une fiche prestataire : un numéro se compose (`tel:`).
- **Répéter la même phrase dans le titre et la description d'un état vide.** La
  description dit ce qui se passera, pas ce qui manque — le titre l'a déjà dit.
- **Composer un mot en JetBrains Mono.** La chasse fixe est pour les chiffres. Le
  deux-points qui les précède reste dans la police du texte, sans quoi il ouvre un
  blanc de deux caractères avant chaque valeur.
- **Afficher une clé de traduction manquante telle quelle.** Une catégorie inconnue
  du fichier de langue doit retomber sur sa valeur brute, jamais sur
  « ui.moderation.categories.xxx » en toutes lettres.
- **Mettre un tableau à défilement horizontal sur un téléphone.** Six colonnes dans
  390 px coupent la dernière et cassent les noms en deux. Une rangée empilée dit les
  mêmes six choses sans rien couper.
- Introduire une icône rendue par une expression sans l'énumérer là où `icons:sync` la
  trouve — une ligature absente du sous-ensemble s'affiche en toutes lettres.
