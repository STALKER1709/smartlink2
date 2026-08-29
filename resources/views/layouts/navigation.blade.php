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

<nav x-data="{ open: false }" class="border-b border-outline-variant bg-surface-container-lowest">
    <div class="mx-auto max-w-container px-margin-mobile md:px-margin-desktop">
        <div class="flex h-16 justify-between gap-4">
            <div class="flex min-w-0 flex-1">
                <div class="flex shrink-0 items-center">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 text-primary">
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
                       class="px-3 py-1.5 {{ $locale === 'fr' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-low' }}">FR</a>
                    <a href="{{ route('locale.switch', 'en') }}"
                       class="px-3 py-1.5 {{ $locale === 'en' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-low' }}">EN</a>
                </div>

                @auth
                    <a href="{{ route('notifications.index') }}"
                       class="relative inline-flex items-center rounded-full p-2 text-on-surface-variant transition-colors hover:bg-surface-container-low hover:text-on-surface"
                       title="{{ __('Notifications') }}">
                        <span class="material-symbols-outlined" style="font-size: 22px;">notifications</span>
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
                                <span class="material-symbols-outlined shrink-0" style="font-size: 18px;">expand_more</span>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="border-b border-outline-variant px-4 py-3">
                                <p class="truncate text-label-md font-semibold text-on-surface">{{ $utilisateur->name }}</p>
                                <p class="truncate text-label-sm text-on-surface-variant">{{ $utilisateur->email }}</p>
                            </div>

                            @foreach ($menuCompte as $entree)
                                <x-dropdown-link :href="route($entree['route'])" class="flex items-center gap-3">
                                    <span class="material-symbols-outlined shrink-0 text-[18px] text-on-surface-variant" aria-hidden="true">{{ $entree['icone'] }}</span>
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
                    <a href="{{ route('login') }}" class="px-2 text-label-md font-medium text-on-surface-variant hover:text-on-surface">{{ __('Se connecter') }}</a>
                    <a href="{{ route('register') }}" class="rounded-full bg-primary px-5 py-2 font-button-text text-label-md font-semibold text-on-primary transition-colors hover:bg-primary-container">{{ __("S'inscrire") }}</a>
                @endauth
            </div>

            <div class="-me-2 flex items-center gap-1 sm:hidden">
                @auth
                    <a href="{{ route('notifications.index') }}" class="relative inline-flex items-center rounded-full p-2 text-on-surface-variant" title="{{ __('Notifications') }}">
                        <span class="material-symbols-outlined" style="font-size: 22px;">notifications</span>
                        @if ($nonLues > 0)
                            <span class="absolute right-0.5 top-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full bg-error font-label-sm text-label-sm font-bold text-on-error">
                                {{ $nonLues > 9 ? '9+' : $nonLues }}
                            </span>
                        @endif
                    </a>
                @endauth

                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-full p-2 text-on-surface-variant transition duration-150 ease-in-out hover:bg-surface-container-low hover:text-on-surface focus:outline-none">
                    <span class="material-symbols-outlined" x-show="! open">menu</span>
                    <span class="material-symbols-outlined" x-show="open" x-cloak>close</span>
                </button>
            </div>
        </div>
    </div>

    <div x-show="open" x-cloak class="sm:hidden">
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
                                <span class="material-symbols-outlined shrink-0 text-[18px] text-on-surface-variant" aria-hidden="true">{{ $entree['icone'] }}</span>
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
