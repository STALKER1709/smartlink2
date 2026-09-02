@php
    /*
     * La navigation mobile, et la seule.
     *
     * Elle coexistait avec un menu burger qui rendait exactement les mêmes
     * quatre destinations : la même liste à deux endroits, l'un au pouce et
     * l'autre en haut à droite de l'écran, là où le pouce n'arrive pas. Le
     * burger est parti ; ce qu'il portait seul — le profil, les écrans propres
     * au rôle, la déconnexion, la langue, le schéma de couleurs — est passé
     * dans la feuille du cinquième onglet.
     *
     * Quatre destinations au maximum, plus le compte : au-delà, les libellés
     * ne tiennent plus sur 360 px.
     */
    $moi = Auth::user();
    $onglets = \App\Support\NavigationLinks::principaux($moi)->take(4)->values();
    $compte = \App\Support\NavigationLinks::compte($moi);
    $secondaires = \App\Support\NavigationLinks::secondaires($moi);
@endphp

<div x-data="{ feuille: false }" @keydown.escape.window="feuille = false" class="md:hidden">

    {{-- La feuille, au-dessus de la barre et sous son propre voile. Elle
         s'ouvre par le bas parce que c'est de là qu'on l'appelle : un panneau
         qui descend du haut oblige le regard à traverser l'écran.

         L'ordre d'empilement du bas de l'écran, du fond vers la surface :
         barre d'action de page (30) · barre d'onglets (40) · bulle de
         l'assistant (50) · voile (60) · feuille (70). La feuille est modale :
         tout ce qui flotte doit passer dessous, sinon la bulle de l'assistant
         se pose sur « Se déconnecter » — ce qu'elle faisait. --}}
    <div x-show="feuille" x-cloak
         @click="feuille = false"
         x-transition.opacity
         class="fixed inset-0 z-[60] bg-scrim/40"
         aria-hidden="true"></div>

    <div x-show="feuille" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
         id="feuille-compte" role="dialog" aria-modal="true" aria-labelledby="feuille-compte-titre"
         class="fixed inset-x-0 bottom-0 z-[70] max-h-[80vh] overflow-y-auto rounded-t-xl border-t border-outline-variant bg-surface-container-lowest pb-[calc(env(safe-area-inset-bottom,0px)+1rem)] shadow-overlay">

        {{-- La poignée : elle dit que le panneau se ferme, avant qu'on ait
             cherché comment. --}}
        <div class="sticky top-0 flex items-center justify-between gap-3 border-b border-outline-variant bg-surface-container-lowest px-4 py-3">
            @auth
                <div class="min-w-0">
                    <p id="feuille-compte-titre" class="truncate font-body-md text-body-md font-semibold text-on-surface">{{ $moi->name }}</p>
                    <p class="truncate font-label-md text-label-md text-on-surface-variant">{{ $moi->email }}</p>
                </div>
            @else
                <p id="feuille-compte-titre" class="font-body-md text-body-md font-semibold text-on-surface">{{ __('Compte') }}</p>
            @endauth

            <button type="button" @click="feuille = false"
                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-on-surface-variant transition-colors hover:bg-surface-container-low hover:text-on-surface"
                    aria-label="{{ __('Fermer') }}">
                <x-icon name="close" size="lg" />
            </button>
        </div>

        @auth
            <ul class="p-2">
                @foreach ($secondaires as $entree)
                    <li>
                        <a href="{{ route($entree['route']) }}"
                           @class([
                               'flex min-h-11 items-center gap-3 rounded-lg px-3 py-2.5 font-body-md text-body-md transition-colors',
                               'bg-primary-container/15 font-semibold text-primary' => request()->routeIs($entree['route']),
                               'text-on-surface hover:bg-surface-container-low' => ! request()->routeIs($entree['route']),
                           ])>
                            <x-icon :name="$entree['icone']" size="sm" class="shrink-0 text-on-surface-variant" />
                            {{ $entree['libelle'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <form method="POST" action="{{ route('logout') }}" class="border-t border-outline-variant p-2">
                @csrf
                <button type="submit"
                        class="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 py-2.5 font-body-md text-body-md text-on-surface transition-colors hover:bg-surface-container-low">
                    <x-icon name="logout" size="sm" class="shrink-0 text-on-surface-variant" />
                    {{ __('Log Out') }}
                </button>
            </form>
        @else
            <div class="space-y-2 p-4">
                <x-primary-button :href="route('register')" class="w-full justify-center">{{ __("S'inscrire") }}</x-primary-button>
                <x-secondary-button :href="route('login')" class="w-full justify-center">{{ __('Se connecter') }}</x-secondary-button>
                <a href="{{ route('help.index') }}"
                   class="flex min-h-11 items-center gap-3 rounded-lg px-3 font-body-md text-body-md text-on-surface transition-colors hover:bg-surface-container-low">
                    <x-icon name="help" size="sm" class="shrink-0 text-on-surface-variant" />
                    {{ __('Aide') }}
                </a>
            </div>
        @endauth

        {{-- Langue et schéma de couleurs : deux réglages de confort, côte à
             côte, qui se lisent pareil. Ils n'existaient sur téléphone que dans
             le burger — les retirer sans leur donner cette place les aurait
             rendus injoignables. --}}
        <div class="flex items-center gap-2 border-t border-outline-variant px-4 py-3">
            <div class="flex overflow-hidden rounded-full border border-outline-variant">
                @foreach (['fr' => __('Français'), 'en' => __('English')] as $code => $libelle)
                    <a href="{{ route('locale.switch', $code) }}"
                       @class([
                           'inline-flex min-h-11 items-center px-4 font-label-md text-label-md font-medium transition-colors',
                           'bg-primary text-on-primary' => app()->getLocale() === $code,
                           'text-on-surface-variant hover:bg-surface-container-low' => app()->getLocale() !== $code,
                       ])>{{ $libelle }}</a>
                @endforeach
            </div>

            <x-theme-switch class="ms-auto" />
        </div>
    </div>

    {{-- La barre elle-même. --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-outline-variant bg-surface-container-lowest"
         aria-label="{{ __('Navigation principale') }}">
        <div class="mx-auto flex max-w-md items-stretch">
            @foreach ($onglets as $onglet)
                @php $actif = request()->routeIs($onglet['motif']); @endphp
                <a href="{{ route($onglet['route']) }}"
                   @if ($actif) aria-current="page" @endif
                   class="flex min-h-14 flex-1 flex-col items-center gap-0.5 px-1 pb-1.5 pt-2 text-center">
                    <span @class([
                        'flex h-7 w-14 items-center justify-center rounded-full transition-colors',
                        'bg-primary-container/15 text-primary' => $actif,
                        'text-on-surface-variant' => ! $actif,
                    ])>
                        <x-icon :name="$onglet['icone']" size="lg" />
                    </span>
                    <span @class([
                        'font-label-sm text-label-sm leading-tight',
                        'font-semibold text-primary' => $actif,
                        'text-on-surface-variant' => ! $actif,
                    ])>{{ $onglet['court'] }}</span>
                </a>
            @endforeach

            <button type="button" @click="feuille = ! feuille"
                    :aria-expanded="feuille ? 'true' : 'false'" aria-controls="feuille-compte"
                    class="flex min-h-14 flex-1 flex-col items-center gap-0.5 px-1 pb-1.5 pt-2 text-center">
                <span :class="feuille ? 'bg-primary-container/15 text-primary' : 'text-on-surface-variant'"
                      class="flex h-7 w-14 items-center justify-center rounded-full transition-colors">
                    <x-icon :name="$compte['icone']" size="lg" />
                </span>
                <span :class="feuille ? 'font-semibold text-primary' : 'text-on-surface-variant'"
                      class="font-label-sm text-label-sm leading-tight">{{ $compte['court'] }}</span>
            </button>
        </div>

        {{-- Réserve la zone d'accueil du geste de retour sur iOS et sur les
             Android à navigation gestuelle : sans elle, le dernier onglet est
             sous la barre système et ne répond pas. --}}
        <div style="height: env(safe-area-inset-bottom, 0px);"></div>
    </nav>
</div>
