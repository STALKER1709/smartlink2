---
name: SmartLink
description: Place de marché de services de proximité au Cameroun — mise en relation, jamais transaction.
colors:
  primary: "#005538"
  on-primary: "#ffffff"
  primary-container: "#0f6f4c"
  on-primary-container: "#9aefc3"
  secondary: "#2a694f"
  on-secondary: "#ffffff"
  secondary-container: "#aff1cf"
  on-secondary-container: "#317054"
  tertiary: "#7b3500"
  on-tertiary: "#ffffff"
  tertiary-container: "#a04700"
  on-tertiary-container: "#ffd4bf"
  error: "#ba1a1a"
  on-error: "#ffffff"
  error-container: "#ffdad6"
  on-error-container: "#93000a"
  surface: "#f9faf7"
  surface-container-lowest: "#ffffff"
  surface-container-low: "#f3f4f1"
  surface-container: "#edeeeb"
  surface-container-high: "#e7e8e6"
  on-surface: "#191c1b"
  on-surface-variant: "#3f4943"
  inverse-surface: "#2e312f"
  inverse-on-surface: "#f0f1ee"
  outline: "#6f7a72"
  outline-variant: "#bec9c0"
typography:
  headline-xl:
    fontFamily: "Hanken Grotesk, ui-sans-serif, system-ui, sans-serif"
    fontSize: "36px"
    fontWeight: 800
    lineHeight: "44px"
    letterSpacing: "-0.02em"
  headline-lg:
    fontFamily: "Hanken Grotesk, ui-sans-serif, system-ui, sans-serif"
    fontSize: "28px"
    fontWeight: 700
    lineHeight: "34px"
    letterSpacing: "-0.01em"
  headline-md:
    fontFamily: "Hanken Grotesk, ui-sans-serif, system-ui, sans-serif"
    fontSize: "22px"
    fontWeight: 600
    lineHeight: "28px"
    letterSpacing: "-0.01em"
  body-lg:
    fontFamily: "Source Sans 3, ui-sans-serif, system-ui, sans-serif"
    fontSize: "18px"
    fontWeight: 400
    lineHeight: "26px"
  body-md:
    fontFamily: "Source Sans 3, ui-sans-serif, system-ui, sans-serif"
    fontSize: "16px"
    fontWeight: 400
    lineHeight: "24px"
  button-text:
    fontFamily: "Source Sans 3, ui-sans-serif, system-ui, sans-serif"
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
  unit: "4px"
  gutter: "16px"
  margin-mobile: "16px"
  margin-desktop: "32px"
  container-max-width: "1200px"
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

## Colors

- **Vert principal `#005538`** : actions clés, chiffres qui comptent, état actif. Sa
  variante `primary-container #0f6f4c` sert le survol et l'appui.
- **Vert clair `secondary-container #aff1cf`** : fonds de pastilles, sélection, zones de
  succès. Employé à faible opacité (`/40`, `/60`) pour rester en arrière-plan.
- **Ambre `tertiary #7b3500`** : réservé aux statuts professionnels et aux
  avertissements — abonnement qui approche de l'échéance, plafond atteint, demande en
  attente. Jamais décoratif.
- **Rouge `error #ba1a1a`** : erreurs et actions destructives seulement.
- **Surfaces** : le fond de page `#f9faf7` porte les contrôles — recherche, filtres —
  tandis que les listes de résultats reposent sur du blanc, de bord à bord sur mobile.
  C'est cette différence de un ton, et non une ombre, qui sépare ce sur quoi on agit de ce
  qu'on lit.

Le vert et l'ambre ne se disputent jamais le même écran comme accents concurrents : si
un avertissement est présent, il est le seul point ambre visible.

## Typography

Deux intentions, deux familles.

- **Hanken Grotesk** pour les titres, interlettrage resserré. Robuste, professionnel.
- **Source Sans 3** pour le corps. Priorité absolue à la lisibilité sur petit écran.
- **JetBrains Mono** pour **toute donnée chiffrée** : prix, dates, références,
  compteurs. La chasse fixe aligne les colonnes de montants dans une liste, et distingue
  au premier coup d'œil une donnée d'une phrase.

Les termes techniques français sont plus longs que leurs équivalents anglais. Les
libellés de boutons et d'onglets doivent être éprouvés à 390 px avant d'être adoptés, pas
après.

## Layout

- **Mobile d'abord, 390 px comme largeur de référence.** Marges latérales de 16 px,
  32 px à partir de `md`.
- **Palier `xs` à 480 px.** Le premier palier de Tailwind est à 640 px, ce qui laisse
  tous les téléphones du mauvais côté. En dessous de `xs`, les grilles passent à une
  colonne et les cartes de service adoptent une disposition horizontale.
- **Conteneur** centré à 1 200 px au maximum.
- **Rythme vertical** de 24 à 32 px entre blocs. Grille de base de 4 px.
- **Barre d'onglets basse sur mobile** (`md:hidden`), cinq entrées au maximum. Le contenu
  réserve `4.75rem` en bas pour ne pas passer dessous.
- **Zones tactiles** : 16 px de rembourrage interne minimum, 56 px de hauteur de ligne
  dans les listes.

## Elevation & Depth

La profondeur vient des **bordures de 1 px** (`outline-variant #bec9c0`), pas des ombres.
C'est la méthode principale pour délimiter une zone d'interaction.

Seuls les menus contextuels et les modales portent une ombre, légère et diffuse.

**Rien ne se soulève au survol.** Un élément qui bouge déplace ce que l'œil suivait, et
répété sur toute une liste il rend la page instable. Le survol et l'état sélectionné se
marquent par un **changement de fond** — vers `surface-container-low`, ou vers le vert
clair pour une sélection.

## Shapes

- **Cartes, champs, conteneurs** : `rounded-xl` (12 px). Accueillant sans être mou.
- **Boutons, pastilles, onglets** : `rounded-full`. Le contraste entre le rectangle
  arrondi des structures et la pilule des actions rend les zones cliquables lisibles sans
  couleur supplémentaire.
- **Vignettes d'illustration** : `rounded-lg`, carrées dans les rangées de liste.
- **Rangées de liste** : aucun rayon. Un filet les sépare, rien ne les encadre.

## Components

- **Rangée de service — une liste, pas une carte.** Un annuaire se parcourt de haut en
  bas : vignette carrée à gauche, métier en capitales, titre, prestataire et lieu, prix
  en JetBrains Mono. Les rangées se séparent par un filet et **ne portent aucune boîte**.
  Deux colonnes seulement à partir de `lg`, et ce sont toujours des rangées.

  Le filet appartient à la rangée, jamais au conteneur : `divide-y` de Tailwind remet
  `border-bottom` à zéro sur tous les enfants **sauf le premier**, ce qui ne laisse qu'une
  seule ligne visible dès qu'on passe en deux colonnes.
- **Colonne de statistique** — chiffre, libellé, indication, séparés par un filet
  vertical. Ni boîte, ni icône décorative : quatre pictogrammes alignés n'apprennent rien
  que le libellé ne dise déjà. La ligne d'indication est **toujours réservée**, même vide :
  les colonnes d'une rangée s'étirent à la hauteur de la plus haute, et sans cette réserve
  les chiffres cessent de s'aligner.
- **État vide** — aligné à gauche, sans cadre en pointillés ni grande icône grise. C'est
  une phrase adressée au visiteur, pas un panneau d'absence.
- **Boutons** — rembourrage horizontal généreux : un libellé français touche les bords
  d'un bouton dimensionné pour l'anglais. Primaire vert plein ; secondaire blanc à
  bordure verte.
- **Champs de saisie** — étiquette toujours visible au-dessus du champ, jamais en
  substitut à l'intérieur. Bordure grise qui passe au vert au focus.
- **Pastilles de statut** — fond coloré à faible opacité, texte en couleur pleine.
- **Barre d'onglets basse** — icône dans une pastille verte quand l'onglet est actif,
  libellé court dessous. Les libellés diffèrent de ceux de la barre large : « Accueil »
  et non « Tableau de bord ».
- **Illustrations de couverture** — quatorze motifs par métier, produits à partir de
  cette palette. Une catégorie sans motif retombe sur l'icône du métier, jamais sur un
  rectangle gris.

## Do's and Don'ts

**À faire**

- Écrire les montants avec l'espace insécable des milliers : « 5 000 FCFA », en
  JetBrains Mono.
- Énoncer clairement que SmartLink ne perçoit aucun paiement entre client et
  prestataire, partout où un prix apparaît.
- Éprouver toute mise en page à 390 px **avant** de la juger sur un écran large.
- Réserver l'ambre aux statuts et avertissements.
- Faire porter les états vides par une action : ce qu'il faut faire, pas ce qui manque.

**À ne pas faire**

- **Faire d'une boîte le motif de mise en page.** Une carte blanche à coin arrondi,
  bordée d'un pixel, posée sur du gris et répétée quinze fois ne hiérarchise rien : chaque
  élément pèse exactement autant que le suivant. C'est la signature d'une interface
  produite sans intention. Une liste séparée par des filets dit la même chose, en plus
  dense et en plus rapide.
- **Emboîter une carte dans une carte.** Jamais.
- **Soulever un élément au survol.** Le changement de fond suffit, et il ne déplace pas
  ce que l'œil suivait.
- Ajouter une ombre là où une bordure suffit.
- Employer une couleur pour décorer.
- Placer une action principale dans la barre de titre d'une section à laquelle elle
  n'appartient pas — sur mobile elle passe à la ligne et paraît agir sur ce titre.
- Répéter la même information dans deux tuiles voisines.
- Laisser un libellé se tronquer : le raccourcir à la source plutôt que de le couper à
  l'affichage.
- Introduire une icône rendue par une expression sans l'énumérer là où `icons:sync` la
  trouve — une ligature absente du sous-ensemble s'affiche en toutes lettres.
