@php
    $utilisateur = Auth::user();
    $locale = app()->getLocale();

    $liens = \App\Support\NavigationLinks::principaux($utilisateur);
    $secondaires = \App\Support\NavigationLinks::secondaires($utilisateur);
    $nonLues = $utilisateur?->unreadNotifications()->count() ?? 0;

    // Le portrait appartient au profil du rôle : logo pour un prestataire,
    // photo pour un client. Un administrateur n'a ni l'un ni l'autre et garde
    // son initiale.
    $photo = $utilisateur?->isProvider()
        ? $utilisateur->providerProfile?->logo_path
        : $utilisateur?->clientProfile?->photo_path;

    $roleLisible = match (true) {
        $utilisateur?->isProvider() => 'Prestataire',
        $utilisateur?->isClient() => 'Client',
        $utilisateur?->isAdmin() => 'Administrateur',
        default => null,
    };

    $ville = $utilisateur?->isProvider()
        ? $utilisateur->providerProfile?->city
        : $utilisateur?->clientProfile?->city;

    $pilule = 'flex items-center gap-3 rounded-full px-4 py-2.5 transition-colors';
    $piluleActive = $pilule.' bg-primary-container/40 font-semibold text-on-secondary-container';
    $piluleInactive = $pilule.' text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface';
@endphp

{{--
    Le tiroir de navigation des maquettes, à partir de 1 280 px.

    Les maquettes le déclenchent à 768 px, et le premier essai l'a posé à
    1 024. Les deux sont trop tôt, pour une raison qui ne se voit qu'à
    l'écran : un tiroir fixé de 320 px rétrécit le contenu sans que les
    requêtes de média le sachent — elles mesurent la fenêtre, pas la place
    qui reste. À 1 024 px, `lg:grid-cols-6` du tableau de bord d'administration
    s'appliquait donc à 704 px de large, et « Prestataires » se lisait
    « Prestatair… ». À 1 280 px il reste 960 px de contenu, soit la largeur
    d'un écran de bureau ordinaire, et toutes les grilles retrouvent leur air.

    La navigation a donc trois formes, chacune sur sa plage — barre d'onglets
    en bas sous 768, barre horizontale entre 768 et 1 279, tiroir au-delà.

    Il n'est pas replié ni escamotable : à cette largeur, l'écran a la place,
    et un tiroir qu'il faut ouvrir pour savoir où l'on est ne vaut pas mieux
    que le menu déroulant qu'il remplace.
--}}
<aside class="fixed inset-y-0 left-0 z-40 hidden w-80 flex-col border-r border-outline-variant bg-surface-container-low xl:flex">
    <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2 border-b border-outline-variant px-6 py-5 text-primary">
        <x-application-logo class="block h-8 w-8" />
        <span class="font-headline-md text-headline-md text-primary">SmartLink</span>
    </a>

    @auth
        <div class="flex shrink-0 items-center gap-3 border-b border-outline-variant px-6 py-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full border border-outline-variant bg-surface-container">
                @if ($photo)
                    <img src="{{ media_url($photo) }}" alt="" class="h-full w-full object-cover" onerror="this.remove()">
                @else
                    <span class="font-headline-md text-lg font-bold text-primary">{{ Str::upper(Str::substr($utilisateur->name, 0, 1)) }}</span>
                @endif
            </div>

            <div class="min-w-0">
                <p class="truncate font-headline-md text-base font-bold text-primary">{{ $utilisateur->name }}</p>
                <p class="truncate text-sm text-on-surface-variant">
                    {{ $roleLisible }}@if ($ville) · {{ $ville }} @endif
                </p>
            </div>
        </div>
    @endauth

    <nav class="min-h-0 flex-1 overflow-y-auto px-3 py-4" aria-label="{{ __("Main navigation") }}">
        <ul class="space-y-1">
            @foreach ($liens as $lien)
                <li>
                    <a href="{{ route($lien['route']) }}"
                       class="{{ request()->routeIs($lien['motif']) ? $piluleActive : $piluleInactive }}"
                       @if (request()->routeIs($lien['motif'])) aria-current="page" @endif>
                        <span class="material-symbols-outlined shrink-0" aria-hidden="true">{{ $lien['icone'] }}</span>
                        <span class="truncate">{{ $lien['libelle'] }}</span>
                    </a>
                </li>
            @endforeach

            @auth
                <li>
                    {{-- Le compteur reste sur la ligne, jamais sous le
                         libellé : c'est ce qui décide qu'on clique. --}}
                    <a href="{{ route('notifications.index') }}"
                       class="{{ request()->routeIs('notifications.*') ? $piluleActive : $piluleInactive }}"
                       @if (request()->routeIs('notifications.*')) aria-current="page" @endif>
                        <span class="material-symbols-outlined shrink-0" aria-hidden="true">notifications</span>
                        <span class="truncate">{{ __('Notifications') }}</span>
                        @if ($nonLues > 0)
                            <span class="ml-auto inline-flex h-5 min-w-[1.25rem] shrink-0 items-center justify-center rounded-full bg-error px-1.5 font-label-numeric text-[11px] font-bold text-on-error">
                                {{ $nonLues > 9 ? '9+' : $nonLues }}
                            </span>
                        @endif
                    </a>
                </li>
            @endauth
        </ul>

        @if ($secondaires->isNotEmpty())
            <p class="px-4 pb-2 pt-6 text-xs font-semibold uppercase tracking-wide text-on-surface-variant">
                {{ __('My account') }}
            </p>

            <ul class="space-y-1">
                @foreach ($secondaires as $entree)
                    <li>
                        <a href="{{ route($entree['route']) }}"
                           class="{{ request()->routeIs($entree['route']) ? $piluleActive : $piluleInactive }}"
                           @if (request()->routeIs($entree['route'])) aria-current="page" @endif>
                            <span class="material-symbols-outlined shrink-0 text-[20px]" aria-hidden="true">{{ $entree['icone'] }}</span>
                            <span class="truncate">{{ $entree['libelle'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </nav>

    <div class="shrink-0 space-y-3 border-t border-outline-variant px-4 py-4">
        <div class="flex overflow-hidden rounded-full border border-outline-variant text-xs font-semibold">
            <a href="{{ route('locale.switch', 'fr') }}"
               class="flex-1 py-1.5 text-center {{ $locale === 'fr' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-high' }}">Français</a>
            <a href="{{ route('locale.switch', 'en') }}"
               class="flex-1 py-1.5 text-center {{ $locale === 'en' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-high' }}">English</a>
        </div>

        @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="{{ $piluleInactive }} w-full hover:text-error">
                    <span class="material-symbols-outlined shrink-0 text-[20px]" aria-hidden="true">logout</span>
                    {{ __('Log Out') }}
                </button>
            </form>
        @else
            <a href="{{ route('register') }}"
               class="flex w-full items-center justify-center rounded-full bg-primary px-5 py-2.5 font-button-text font-semibold text-on-primary transition-colors hover:bg-primary-container">
                {{ __("S'inscrire") }}
            </a>
            <a href="{{ route('login') }}"
               class="flex w-full items-center justify-center rounded-full border border-outline-variant px-5 py-2.5 font-button-text font-semibold text-on-surface transition-colors hover:bg-surface-container-high">
                {{ __('Se connecter') }}
            </a>
        @endauth
    </div>
</aside>
