---
name: SmartLink
colors:
  surface: '#f9faf7'
  surface-dim: '#d9dad7'
  surface-bright: '#f9faf7'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f4f1'
  surface-container: '#edeeeb'
  surface-container-high: '#e7e8e6'
  surface-container-highest: '#e2e3e0'
  on-surface: '#191c1b'
  on-surface-variant: '#3f4943'
  inverse-surface: '#2e312f'
  inverse-on-surface: '#f0f1ee'
  outline: '#6f7a72'
  outline-variant: '#bec9c0'
  surface-tint: '#086c49'
  primary: '#005538'
  on-primary: '#ffffff'
  primary-container: '#0f6f4c'
  on-primary-container: '#9aefc3'
  inverse-primary: '#83d7ad'
  secondary: '#2a694f'
  on-secondary: '#ffffff'
  secondary-container: '#aff1cf'
  on-secondary-container: '#317054'
  tertiary: '#7b3500'
  on-tertiary: '#ffffff'
  tertiary-container: '#a04700'
  on-tertiary-container: '#ffd4bf'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#9ff4c8'
  primary-fixed-dim: '#83d7ad'
  on-primary-fixed: '#002113'
  on-primary-fixed-variant: '#005236'
  secondary-fixed: '#aff1cf'
  secondary-fixed-dim: '#93d4b3'
  on-secondary-fixed: '#002114'
  on-secondary-fixed-variant: '#095138'
  tertiary-fixed: '#ffdbca'
  tertiary-fixed-dim: '#ffb68e'
  on-tertiary-fixed: '#331200'
  on-tertiary-fixed-variant: '#763300'
  background: '#f9faf7'
  on-background: '#191c1b'
  surface-variant: '#e2e3e0'
typography:
  headline-xl:
    fontFamily: Hanken Grotesk
    fontSize: 36px
    fontWeight: '800'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Hanken Grotesk
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 34px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Hanken Grotesk
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  body-lg:
    fontFamily: Source Sans 3
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 26px
  body-md:
    fontFamily: Source Sans 3
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-numeric:
    fontFamily: JetBrains Mono
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
  button-text:
    fontFamily: Source Sans 3
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  unit: 4px
  gutter: 16px
  margin-mobile: 16px
  margin-desktop: 32px
  container-max-width: 1200px
---

## Brand & Style
Le système de design est conçu pour une plateforme de services de proximité au Cameroun. L'esthétique adopte un **Minimalisme Pragmatique** : une interface épurée, axée sur l'utilité immédiate et la clarté opérationnelle. Le design privilégie la lisibilité et la performance, optimisé pour une utilisation intensive sur des appareils Android de milieu de gamme dans des conditions de connectivité variables (3G/4G). L'objectif est d'inspirer une confiance immédiate entre les prestataires et les clients à travers une interface structurée, concrète et sans artifice.

## Colors
La palette est ancrée dans une identité de confiance et d'efficacité.
- **Vert Principal (#0F6F4C)** : Symbole de croissance et de fiabilité, utilisé pour les actions clés.
- **Vert Appuyé (#0B5239)** : Pour les états de survol et les interactions actives.
- **Vert Clair (#E3EFE9)** : Utilisé pour les arrière-plans de sections ou les pastilles de succès.
- **Ambre (#B45309)** : Réservé exclusivement aux statuts professionnels (comptes vérifiés) et aux avertissements.
- **Rouge (#B91C1C)** : Pour les erreurs et les actions destructives.
- **Surfaces** : Un fond très légèrement grisé (#F6F7F4) permet de faire ressortir les cartes blanches (#FFFFFF), créant une hiérarchie visuelle naturelle sans ombres lourdes.

## Typography
La typographie articule deux intentions : l'impact pour les titres et la fonctionnalité pour les données.
- **Titres** : Utilisation de la grotesque contemporaine avec un interlettrage resserré pour un aspect robuste et professionnel.
- **Corps de texte** : Priorité absolue à la lisibilité sur écrans mobiles de petite taille.
- **Données Chiffrées** : L'utilisation d'une police monospacée/tabulaire pour les prix (ex: 2 500 FCFA) et les dates assure un alignement parfait dans les listes de transactions et les factures.
- **Adaptation Locale** : Les tailles de texte sont prévues pour accommoder la longueur supérieure des termes techniques en français par rapport à l'anglais.

## Layout & Spacing
Le système utilise une grille fluide basée sur une unité de 4px.
- **Mobile First** : Marges latérales de 16px par défaut. Les éléments de liste et les cartes occupent 100% de la largeur disponible moins les marges.
- **Conteneurs** : Sur desktop, le contenu est centré avec une largeur maximale de 1200px.
- **Rythme Vertical** : Espacement généreux entre les blocs de contenu (24px ou 32px) pour éviter toute surcharge visuelle sur les petits écrans.
- **Zonage** : Utilisation systématique de paddings internes de 16px minimum pour les zones cliquables afin de faciliter l'interaction tactile.

## Elevation & Depth
Le système rejette les ombres complexes au profit de la clarté structurelle.
- **Bordures de Structure** : La profondeur est créée par des bordures de 1px (#DCE3DC). C'est la méthode principale pour délimiter les zones d'interaction.
- **Superposition** : Seuls les menus contextuels ou les modales utilisent une ombre portée très légère, diffuse et neutre pour se détacher de l'arrière-plan.
- **États Actifs** : Le changement de couleur de fond (du blanc vers le vert clair) indique la sélection plutôt qu'une élévation physique.

## Shapes
Le langage des formes est sobre et "Soft" (4px à 8px de rayon).
- **Cartes et Inputs** : Rayon de 8px (0.5rem) pour un aspect accueillant mais rigoureux.
- **Boutons** : Rayon de 4px (0.25rem) pour renforcer l'aspect utilitaire.
- **Pastilles (Badges)** : Totalement arrondies (Pill-shaped) pour contraster avec les éléments structurels rectangulaires.

## Components
- **Boutons** : Dimensionnés pour le français. Le padding horizontal doit être 20% plus large que la norme standard pour éviter que les mots longs ne touchent les bords. Bouton primaire vert avec texte blanc ; secondaire blanc avec bordure verte.
- **Cartes de Services** : Fond blanc, bordure 1px, sans ombre. Elles doivent afficher clairement le prix en JetBrains Mono et le titre du service en Hanken Grotesk.
- **Champs de Saisie** : Étiquettes (labels) toujours visibles au-dessus du champ. Bordure grise qui passe au vert principal lors du focus.
- **Pastilles de Statut** : Fonds colorés à faible opacité avec texte de couleur pleine (ex: Fond vert clair pour "Terminé", fond ambre pour "En attente").
- **Listes de Services** : Séparateurs horizontaux de 1px. Hauteur de ligne minimale de 56px pour une interaction tactile confortable.
- **Affichage Monétaire** : Toujours inclure l'espace insécable pour les milliers : "5 000 FCFA".