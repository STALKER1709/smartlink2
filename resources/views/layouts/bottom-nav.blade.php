@php
    /*
     * Barre d'onglets basse, mobile uniquement.
     *
     * C'est le motif de navigation des maquettes Stitch, et celui qu'attend un
     * public qui vit sur Android : les destinations sont visibles en
     * permanence et atteignables au pouce, là où le menu déroulant demandait
     * d'aller chercher un bouton en haut de l'écran à chaque changement de page.
     *
     * Elle réutilise la table de `NavigationLinks`, partagée avec la barre large,
     * plus une entrée de compte. Cinq onglets au maximum : au-delà, les
     * libellés ne tiennent plus sur 390 px.
     */
    $moi = Auth::user();
    $onglets = \App\Support\NavigationLinks::principaux($moi)->take(4)->values();
    $compte = \App\Support\NavigationLinks::compte($moi);
@endphp

<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-outline-variant bg-surface-container-lowest md:hidden"
     aria-label="{{ __('Navigation principale') }}">
    <div class="mx-auto flex max-w-md items-stretch">
        @foreach ($onglets as $onglet)
            @php $actif = request()->routeIs($onglet['motif']); @endphp
            <a href="{{ route($onglet['route']) }}"
               @if ($actif) aria-current="page" @endif
               class="flex flex-1 flex-col items-center gap-0.5 px-1 pb-1.5 pt-2 text-center">
                <span @class([
                    'flex h-7 w-14 items-center justify-center rounded-full transition-colors',
                    'bg-primary-container/15 text-primary' => $actif,
                    'text-on-surface-variant' => ! $actif,
                ])>
                    <x-icon :name="$onglet['icone']" size="lg" filled @if ($actif) @endif />
                </span>
                <span @class([
                    'font-label-sm text-label-sm leading-tight',
                    'font-semibold text-primary' => $actif,
                    'text-on-surface-variant' => ! $actif,
                ])>{{ $onglet['court'] }}</span>
            </a>
        @endforeach

        @php $compteActif = request()->routeIs($compte['motif']); @endphp
        <a href="{{ route($compte['route']) }}"
           @if ($compteActif) aria-current="page" @endif
           class="flex flex-1 flex-col items-center gap-0.5 px-1 pb-1.5 pt-2 text-center">
            <span @class([
                'flex h-7 w-14 items-center justify-center rounded-full transition-colors',
                'bg-primary-container/15 text-primary' => $compteActif,
                'text-on-surface-variant' => ! $compteActif,
            ])>
                <x-icon :name="$compte['icone']" size="lg" filled @if ($compteActif) @endif />
            </span>
            <span @class([
                'font-label-sm text-label-sm leading-tight',
                'font-semibold text-primary' => $compteActif,
                'text-on-surface-variant' => ! $compteActif,
            ])>{{ $compte['court'] }}</span>
        </a>
    </div>

    {{-- Réserve la zone d'accueil du geste de retour sur iOS et sur les Android
         à navigation gestuelle : sans elle, le dernier onglet est sous la barre
         système et ne répond pas. --}}
    <div style="height: env(safe-area-inset-bottom, 0px);"></div>
</nav>
