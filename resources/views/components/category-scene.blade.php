@props(['name' => null, 'seed' => null])

{{--
    La vignette d'un métier, dessinée plutôt que photographiée.

    Pourquoi pas une photographie : `DESIGN.md` demande qu'elle soit
    camerounaise et de droits vérifiés. Les banques d'images sous licence
    libre n'ont ni l'une ni l'autre pour ces métiers — un plombier américain
    en vignette de « Plomberie » dessert une place de marché camerounaise plus
    qu'il ne la sert. Un dessin ne ment sur rien.

    Ce qu'on y gagne aussi : aucune requête réseau, aucun octet à télécharger
    sur de la 3G, rien à recadrer, et un rendu identique hors ligne.

    La scène est commune — ciel, soleil bas, ligne d'horizon, sol, feuillage
    au premier plan — et seul le motif change. C'est ce qui fait tenir
    trente et un métiers dans une seule famille visuelle, là où trente et une
    photographies se contrediraient. Le feuillage est celui d'un bananier :
    la scène a un lieu.

    La variation — hauteur du soleil, position du feuillage — est tirée du nom
    de la catégorie, donc stable : la vignette de « Plomberie » est la même à
    chaque rendu, et différente de celle de « Ménage ».
--}}

@php
    // Quatorze motifs pour trente et un métiers. Un motif partagé par des
    // métiers voisins vaut mieux qu'un dessin approximatif par métier.
    $familles = [
        'plomberie' => ['Plomberie', "Livraison d'eau"],
        'electricite' => ['Électricité', 'Générateur & énergie solaire', 'Installation TV & antenne', 'Climatisation & réfrigération'],
        'maconnerie' => ['Maçonnerie', 'Carrelage', 'Peinture'],
        'menuiserie' => ['Menuiserie', 'Soudure & ferronnerie'],
        'coiffure' => ['Coiffure', 'Maquillage & ongles', 'Massage & soins'],
        'couture' => ['Couture & stylisme'],
        'menage' => ['Ménage', 'Aide à domicile'],
        'jardinage' => ['Jardinage'],
        'cours' => ['Cours particuliers', "Garde d'enfants"],
        'informatique' => ['Informatique & réparation', 'Réparation téléphone'],
        'transport' => ['Moto-taxi', 'Location de véhicules', 'Déménagement', 'Mécanique auto & moto'],
        'cuisine' => ['Traiteur & cuisine', 'Vente de vivres'],
        'evenement' => ['Animation & DJ', 'Photographie & vidéo'],
        'securite' => ['Gardiennage'],
    ];

    $motif = 'menuiserie';
    foreach ($familles as $cle => $metiers) {
        if (in_array($name, $metiers, true)) {
            $motif = $cle;
            break;
        }
    }

    // Stable pour un nom donné : la même catégorie garde sa scène.
    $graine = crc32((string) ($seed ?? $name ?? 'smartlink'));
    /*
     * Le soleil vit dans une fenêtre étroite, et ce n'est pas un caprice.
     *
     * `slice` recadre la scène sur son centre, et le cadrage dépend du format
     * de la vignette : une carte de service en montre la bande 57–343, la
     * vignette carrée de l'annuaire seulement 100–300. Un soleil posé hors de
     * la plus étroite finit coupé net par un bord.
     *
     * En hauteur il passe sous la rangée des pastilles — posé plus haut, le
     * cœur des favoris lui découpait un croissant, qu'on prenait pour un
     * défaut de rendu — et il reste au-dessus de l'horizon.
     */
    $soleilX = 240 + ($graine % 30);
    $soleilY = 84 + (intdiv($graine, 7) % 12);
    $feuilleX = 6 + (intdiv($graine, 13) % 20); // ancré en bas à gauche
    $horizon = 148 + (intdiv($graine, 31) % 8);
@endphp

<svg {{ $attributes->merge(['class' => 'h-full w-full']) }}
     viewBox="0 0 400 200" preserveAspectRatio="xMidYMid slice"
     role="img" aria-label="{{ $name ? __('Illustration : :metier', ['metier' => $name]) : __('Illustration') }}">

    {{-- Le ciel. Un dégradé, pas un aplat : c'est ce qui donne l'heure. --}}
    <defs>
        <linearGradient id="ciel-{{ $motif }}-{{ $graine % 997 }}" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="var(--scene-ciel-haut)" />
            <stop offset="100%" stop-color="var(--scene-ciel-bas)" />
        </linearGradient>
    </defs>

    <rect width="400" height="200" fill="url(#ciel-{{ $motif }}-{{ $graine % 997 }})" />

    {{-- Le soleil bas, dans le jaune de la palette. --}}
    <circle cx="{{ $soleilX }}" cy="{{ $soleilY }}" r="26" fill="var(--scene-soleil)" opacity="0.85" />

    {{-- Le sol, et la ligne d'horizon qui le pose. --}}
    <path d="M0 {{ $horizon }} H400 V200 H0 Z" fill="var(--scene-sol)" />
    <path d="M0 {{ $horizon }} H400" stroke="var(--scene-horizon)" stroke-width="2" fill="none" />

    {{-- Le motif du métier, centré sur la ligne d'horizon. --}}
    <g transform="translate(200 {{ $horizon - 4 }}) scale(0.82)" fill="none"
       stroke="var(--scene-trait)" stroke-width="7"
       stroke-linecap="round" stroke-linejoin="round">
        @include('partials.scenes.'.$motif)
    </g>

    {{-- Le feuillage de bananier, au premier plan et à gauche : il donne la
         profondeur et il situe la scène. --}}
    <g transform="translate({{ $feuilleX }} 200)" fill="var(--scene-feuille)" opacity="0.5">
        <path d="M0 0 C-10 -44 4 -84 34 -104 C22 -70 16 -34 22 0 Z" />
        <path d="M18 0 C28 -36 52 -64 88 -76 C64 -50 48 -24 44 0 Z" opacity="0.7" />
        <path d="M-30 0 C-34 -26 -24 -50 -6 -62 C-14 -40 -16 -18 -12 0 Z" opacity="0.55" />
    </g>
</svg>
