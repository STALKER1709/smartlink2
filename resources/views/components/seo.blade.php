@props([
    // Le titre propre à la page, sans le nom du site : il est ajouté ici.
    'titre' => null,
    'description' => null,
    // Image d'aperçu au partage. Une fiche de service passe la sienne ; à
    // défaut, le visuel de marque.
    'image' => null,
    // « website » partout, « article » ou « profile » sur les pages qui
    // décrivent une chose précise — ce que lisent les aperçus de partage.
    'type' => 'website',
    // Les pages privées ou filtrées n'ont rien à faire dans un index.
    'indexable' => true,
])

@php
    $nomDuSite = config('app.name', 'SmartLink');

    /*
     * Un onglet lit son titre de gauche à droite et se rogne par la droite :
     * ce qui distingue la page passe donc en premier, le nom du site ensuite.
     */
    $titreComplet = filled($titre) ? $titre.' · '.$nomDuSite : __('seo.default_title');

    /*
     * Les retours à la ligne d'une description saisie librement casseraient
     * l'attribut ; la ponctuation de fin est retirée avant la coupe, sans quoi
     * un texte finissant par un point ressort en « … ».
     */
    $descriptionFinale = Str::limit(
        rtrim(trim(preg_replace('/\s+/u', ' ', $description ?? __('seo.default_description'))), " .,;:-"),
        155,
        '…',
    );

    $imageFinale = $image ?: asset('images/partage-smartlink.png');

    /*
     * L'URL canonique porte le chemin sans la chaîne de requête : sans elle,
     * chaque combinaison de filtres et chaque page de pagination compteraient
     * comme autant de pages distinctes au contenu presque identique.
     */
    $canonique = url()->current();
@endphp

<title>{{ $titreComplet }}</title>
<meta name="description" content="{{ $descriptionFinale }}">
<link rel="canonical" href="{{ $canonique }}">

@unless ($indexable)
    <meta name="robots" content="noindex, follow">
@endunless

{{-- Aperçu au partage : c'est ce que composent WhatsApp et Facebook, par où
     passe l'essentiel du partage de la plateforme. --}}
<meta property="og:site_name" content="{{ $nomDuSite }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $titreComplet }}">
<meta property="og:description" content="{{ $descriptionFinale }}">
<meta property="og:url" content="{{ $canonique }}">
<meta property="og:image" content="{{ $imageFinale }}">
<meta property="og:locale" content="{{ app()->getLocale() === 'en' ? 'en_GB' : 'fr_FR' }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $titreComplet }}">
<meta name="twitter:description" content="{{ $descriptionFinale }}">
<meta name="twitter:image" content="{{ $imageFinale }}">
