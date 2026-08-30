@php
    $utilisateur = Auth::user();
    $locale = app()->getLocale();

    /*
     * La table des destinations vit dans `App\Support\NavigationLinks` : la
     * barre d'onglets du bas, incluse séparément, n'a pas accès aux variables
     * définies ici. La recopier aurait ramené le défaut que cette liste unique
     * corrigeait — une entrée ajoutée d'un côté et manquante de l'autre.
     */
    $liens = \App\Support\NavigationLinks::principaux($utilisateur);

    /*
     * Le menu du compte vient de `NavigationLinks::secondaires` : le menu
     * déroulant et le panneau mobile ci-dessous le rendent tous les deux, pour
     * la raison qui a déjà sorti les liens principaux d'ici — deux listes
     * recopiées divergent.
     */
    $menuCompte = \App\Support\NavigationLinks::secondaires($utilisateur);

    // Une seule fois : la barre large et le menu mobile s'en partagent le résultat.
    $nonLues = $utilisateur?->unreadNotifications()->count() ?? 0;
@endphp

{{-- La barre haute disparaît quand la colonne latérale porte les mêmes
     destinations : les répéter à deux endroits, c'est ce que la charte
     refuse, et la colonne n'existe qu'à partir de `xl`. --}}
<nav x-data="{ open: false }" @class([
    'border-b border-outline-variant bg-surface-container-lowest',
    'xl:hidden' => auth()->check(),
])>
    <div class="mx-auto max-w-container px-margin-mobile md:px-margin-tablet lg:px-margin-desktop">
        <div class="flex h-16 justify-between gap-4">
            <div class="flex min-w-0 flex-1">
                <div class="flex shrink-0 items-center">
                    <a href="{{ route('home') }}" class="-ml-2 inline-flex min-h-11 items-center gap-2 rounded-lg px-2 text-primary transition-colors hover:bg-surface-container-low" aria-label="{{ __('SmartLink — accueil') }}">
                        <x-application-logo class="block h-8 w-8" />
                        <span class="hidden font-headline-md text-headline-md text-primary sm:inline">SmartLink</span>
                    </a>
                </div>

                <div class="hidden min-w-0 space-x-5 sm:-my-px sm:ms-8 sm:flex sm:flex-nowrap">
                    @foreach ($liens as $lien)
                        <x-nav-link :href="route($lien['route'])" :active="request()->routeIs($lien['motif'])">
                            {{ $lien['libelle'] }}
                        </x-nav-link>
                    @endforeach
                </div>
            </div>

            <div class="hidden shrink-0 items-center gap-2 sm:ms-6 sm:flex">
                <div class="flex overflow-hidden rounded-full border border-outline-variant text-label-sm font-semibold">
                    <a href="{{ route('locale.switch', 'fr') }}"
                       class="inline-flex h-11 min-w-11 items-center justify-center px-3 font-label-md text-label-md transition-colors {{ $locale === 'fr' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-low' }}">FR</a>
                    <a href="{{ route('locale.switch', 'en') }}"
                       class="inline-flex h-11 min-w-11 items-center justify-center px-3 font-label-md text-label-md transition-colors {{ $locale === 'en' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-low' }}">EN</a>
                </div>

                @auth
                    <a href="{{ route('notifications.index') }}"
                       class="relative inline-flex items-center rounded-full p-2 text-on-surface-variant transition-colors hover:bg-surface-container-low hover:text-on-surface"
                       title="{{ __('Notifications') }}">
                        <x-icon name="notifications" style="font-size: 22px;" />
                        @if ($nonLues > 0)
                            <span class="absolute right-0.5 top-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full bg-error font-label-sm text-label-sm font-bold text-on-error">
                                {{ $nonLues > 9 ? '9+' : $nonLues }}
                            </span>
                        @endif
                    </a>

                    <x-dropdown align="right" width="56">
                        <x-slot name="trigger">
                            <button class="inline-flex max-w-[10rem] items-center gap-1 rounded-full border border-transparent bg-surface-container-lowest px-3 py-2 text-label-md font-medium leading-4 text-on-surface-variant transition duration-150 ease-in-out hover:bg-surface-container-low hover:text-on-surface focus:outline-none">
                                <span class="truncate">{{ $utilisateur->name }}</span>
                                <x-icon name="expand_more" class="shrink-0" style="font-size: 18px;" />
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="border-b border-outline-variant px-4 py-3">
                                <p class="truncate text-label-md font-semibold text-on-surface">{{ $utilisateur->name }}</p>
                                <p class="truncate text-label-sm text-on-surface-variant">{{ $utilisateur->email }}</p>
                            </div>

                            @foreach ($menuCompte as $entree)
                                <x-dropdown-link :href="route($entree['route'])" class="flex items-center gap-3">
                                    <x-icon :name="$entree['icone']" size="sm" class="shrink-0 text-on-surface-variant" />
                                    {{ $entree['libelle'] }}
                                </x-dropdown-link>
                            @endforeach

                            <form method="POST" action="{{ route('logout') }}" class="border-t border-outline-variant">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                                 onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center rounded-full px-4 text-label-md font-medium text-on-surface-variant transition-colors hover:bg-surface-container-low hover:text-on-surface">{{ __('Se connecter') }}</a>
                    <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center rounded-full bg-primary px-5 font-button-text text-label-md font-semibold text-on-primary transition-colors hover:bg-primary-container">{{ __("S'inscrire") }}</a>
                @endauth
            </div>

            <div class="-me-2 flex items-center gap-1 sm:hidden">
                @auth
                    <a href="{{ route('notifications.index') }}" class="relative inline-flex items-center rounded-full p-2 text-on-surface-variant" title="{{ __('Notifications') }}">
                        <x-icon name="notifications" style="font-size: 22px;" />
                        @if ($nonLues > 0)
                            <span class="absolute right-0.5 top-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full bg-error font-label-sm text-label-sm font-bold text-on-error">
                                {{ $nonLues > 9 ? '9+' : $nonLues }}
                            </span>
                        @endif
                    </a>
                @endauth

                {{-- 44 px de côté : `p-2` autour d'une icône de 20 px n'en donnait que 36,
                     mesure prise dans le navigateur. C'est le seul bouton de la barre
                     sur un téléphone ; il n'a pas droit à l'à-peu-près. --}}
                <button @click="open = ! open"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-full text-on-surface-variant transition-colors hover:bg-surface-container-low hover:text-on-surface"
                        :aria-expanded="open ? 'true' : 'false'"
                        aria-controls="menu-mobile"
                        aria-label="{{ __('Menu') }}">
                    <x-icon name="menu" size="lg" x-show="! open" />
                    <x-icon name="close" size="lg" x-show="open" x-cloak />
                </button>
            </div>
        </div>
    </div>

    <div id="menu-mobile" x-show="open" x-cloak class="sm:hidden">
        <div class="space-y-1 pb-3 pt-2">
            @foreach ($liens as $lien)
                <x-responsive-nav-link :href="route($lien['route'])" :active="request()->routeIs($lien['motif'])">
                    {{ $lien['libelle'] }}
                </x-responsive-nav-link>
            @endforeach
        </div>

        <div class="border-t border-outline-variant pb-1 pt-4">
            @auth
                <div class="px-4">
                    <div class="text-body-md font-medium text-on-surface">{{ $utilisateur->name }}</div>
                    <div class="text-label-md font-medium text-on-surface-variant">{{ $utilisateur->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    @foreach ($menuCompte as $entree)
                        <x-responsive-nav-link :href="route($entree['route'])">
                            <span class="inline-flex items-center gap-3">
                                <x-icon :name="$entree['icone']" size="sm" class="shrink-0 text-on-surface-variant" />
                                {{ $entree['libelle'] }}
                            </span>
                        </x-responsive-nav-link>
                    @endforeach

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                                               onclick="event.preventDefault(); this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            @else
                <div class="space-y-1">
                    <x-responsive-nav-link :href="route('login')">{{ __('Se connecter') }}</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('register')">{{ __("S'inscrire") }}</x-responsive-nav-link>
                </div>
            @endauth
        </div>

        <div class="flex gap-2 border-t border-outline-variant px-4 py-3">
            <a href="{{ route('locale.switch', 'fr') }}"
               class="rounded-full px-3 py-1.5 text-label-md font-medium {{ $locale === 'fr' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-low' }}">{{ __("Français") }}</a>
            <a href="{{ route('locale.switch', 'en') }}"
               class="rounded-full px-3 py-1.5 text-label-md font-medium {{ $locale === 'en' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-low' }}">{{ __("English") }}</a>
        </div>
    </div>
</nav>
