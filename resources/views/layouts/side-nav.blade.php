@php
    $utilisateur = auth()->user();
    $principaux = \App\Support\NavigationLinks::principaux($utilisateur);
    $secondaires = \App\Support\NavigationLinks::secondaires($utilisateur);
    $profil = $utilisateur?->providerProfile;

    // La maquette met l'action principale du rôle juste sous l'identité, en
    // pleine largeur : c'est la première chose qu'on vient faire.
    $action = match (true) {
        (bool) $utilisateur?->isProvider() => ['route' => 'provider.services.create', 'icone' => 'add_circle', 'libelle' => __('Publier un service')],
        (bool) $utilisateur?->isClient() => ['route' => 'services.index', 'icone' => 'search', 'libelle' => __('Trouver un prestataire')],
        default => null,
    };

    $lienBase = 'flex items-center gap-3 rounded-lg p-3 font-label-md text-label-md transition-colors';
    $lienActif = 'bg-secondary-container font-bold text-on-secondary-container';
    $lienInerte = 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface';
@endphp

{{--
    La colonne latérale de bureau, reprise de la maquette Stitch — elle porte
    vingt écrans sur vingt-six du projet.

    Un seuil, et une raison mesurée. La maquette la fait apparaître à `md`,
    soit 768 px : avec ses 256 px de colonne, il ne resterait que 512 px de
    contenu sur une tablette.

    Essayée à `lg` (1 024 px), elle laissait 768 px — et la page débordait de
    29 px, mesure prise dans le navigateur. C'est le défaut que la charte
    documente depuis le tiroir précédent : une requête de média mesure la
    *fenêtre*, pas la place qui reste. À 1 024 px de fenêtre, les classes
    `lg:` du contenu croient disposer de 1 024 px quand elles en ont 768.

    Elle apparaît donc à `xl` (1 280 px), où il reste exactement 1 024 px —
    la largeur que les classes `lg:` attendent. C'est le seul écart pris sur
    la mise en page de la maquette.

    Les destinations viennent de `NavigationLinks`, comme la barre haute et la
    barre d'onglets. Recopiées, elles dériveraient au premier écran ajouté
    d'un seul côté.
--}}
<nav class="fixed left-0 top-0 z-40 hidden h-full w-64 flex-col border-r border-outline-variant bg-surface-container-low p-4 xl:flex"
     aria-label="{{ __('Navigation principale') }}">

    <div class="mb-6 flex flex-col items-center text-center">
        <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 rounded-lg p-2 transition-colors hover:bg-surface-container">
            <span class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-2 border-primary bg-surface-container-high">
                @if ($profil?->logo_path)
                    <img src="{{ media_url($profil->logo_path) }}" alt="" class="h-full w-full object-cover" onerror="this.remove()">
                @else
                    <span class="font-display-lg text-headline-lg text-primary">{{ Str::upper(Str::substr($profil->business_name ?? $utilisateur?->name ?? 'S', 0, 1)) }}</span>
                @endif
            </span>
            <span class="font-headline-md text-headline-md text-primary">SmartLink</span>
        </a>

        @if ($profil?->is_verified)
            <p class="mt-1 flex items-center gap-1 font-label-sm text-label-sm text-on-surface-variant">
                <x-icon name="verified" size="xs" class="text-primary" />
                {{ __('Prestataire vérifié') }}
            </p>
        @endif
    </div>

    @if ($action)
        <a href="{{ route($action['route']) }}"
           class="mb-6 flex min-h-11 items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-primary px-3 font-label-md text-label-md text-on-primary shadow-elevation-1 transition-colors hover:bg-primary-container hover:text-on-primary-container">
            <x-icon :name="$action['icone']" size="sm" />
            {{ $action['libelle'] }}
        </a>
    @endif

    <ul class="flex flex-1 flex-col gap-2 overflow-y-auto">
        @foreach ($principaux as $entree)
            <li>
                <a href="{{ route($entree['route']) }}"
                   @class([$lienBase, $lienActif => request()->routeIs($entree['motif']), $lienInerte => ! request()->routeIs($entree['motif'])])
                   @if (request()->routeIs($entree['motif'])) aria-current="page" @endif>
                    <x-icon :name="$entree['icone']" size="sm" />
                    {{ $entree['libelle'] }}
                </a>
            </li>
        @endforeach

        @if ($secondaires->isNotEmpty())
            <li class="my-2" aria-hidden="true"><hr class="border-outline-variant"></li>
            {{-- `secondaires()` ne porte pas de clé `motif` : ses entrées
                 désignent une route exacte, là où `principaux()` couvre une
                 famille (`requests.*`). C'est la route qui sert de motif. --}}
            @foreach ($secondaires as $entree)
                @php $actif = request()->routeIs($entree['route']); @endphp
                <li>
                    <a href="{{ route($entree['route']) }}"
                       @class([$lienBase, $lienActif => $actif, $lienInerte => ! $actif])
                       @if ($actif) aria-current="page" @endif>
                        <x-icon :name="$entree['icone']" size="sm" />
                        {{ $entree['libelle'] }}
                    </a>
                </li>
            @endforeach
        @endif
    </ul>

    <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-outline-variant pt-2">
        @csrf
        <button type="submit" class="{{ $lienBase }} {{ $lienInerte }} w-full">
            <x-icon name="logout" size="sm" />
            {{ __('Se déconnecter') }}
        </button>
    </form>
</nav>
