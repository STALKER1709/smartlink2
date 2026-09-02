<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('partials.theme-head')

        <x-seo :titre="$titre"
               :description="$description"
               :image="$imagePartage"
               :indexable="$indexable" />

        <!-- Fonts -->

        @include('partials.pwa-head')

        <!-- Scripts -->        {{-- Les deux fichiers que toute page française lit, demandés avant que
             le navigateur ne découvre la feuille qui les réclame. Le
             `crossorigin` est obligatoire même en même origine : une police
             se récupère en mode anonyme, et sans lui le préchargement est
             ignoré puis refait. --}}
        <link rel="preload" href="/fonts/inter-400-600-latin.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/lexend-600-700-latin.woff2" as="font" type="font/woff2" crossorigin>
        {{-- La police d'icônes est en `font-display: block` : sans elle, un
             pictogramme n'est pas remplacé par du texte, il est absent. Elle
             se précharge donc au même titre que les deux polices de texte. --}}
        <link rel="preload" href="/fonts/icones-plein.woff2" as="font" type="font/woff2" crossorigin>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-on-surface antialiased">
        {{-- Le premier geste d'un visiteur au clavier, et le seul qui lui
             évite de traverser la barre entière à chaque page. Invisible tant
             qu'il n'a pas le focus, il se pose alors par-dessus tout le
             reste : sans `z-`, la barre collante passait devant lui. --}}
        <a href="#contenu"
           class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:inline-flex focus:min-h-11 focus:items-center focus:rounded-lg focus:bg-primary focus:px-4 focus:font-label-md focus:text-label-md focus:text-on-primary focus:shadow-overlay">
            {{ __('Aller au contenu') }}
        </a>

        {{-- Deux mises en page, selon qu'on est chez soi ou en visite.

             Un visiteur connecté a une colonne latérale à partir de `xl`,
             comme dans la maquette Stitch — elle porte vingt de ses
             vingt-six écrans. Un visiteur de passage garde la barre haute :
             il n'a pas de destinations à lui, et une colonne vide ne
             servirait qu'à rétrécir la page. --}}
        @php $colonne = auth()->check(); @endphp

        <div @class(['min-h-screen bg-surface', 'xl:pl-64' => $colonne])>
            @if ($colonne)
                @include('layouts.side-nav')
            @endif

            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-surface-container-lowest border-b border-outline-variant">
                    <div class="max-w-container mx-auto py-6 px-margin-mobile md:px-margin-tablet lg:px-margin-desktop">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            @include('partials.subscription-banner')

            @include('partials.flash-messages')

            <!-- Page Content -->
            <main id="contenu" tabindex="-1">
                {{ $slot }}
            </main>

            @if ($piedDePage)
                @include('layouts.footer')
            @endif

            {{-- La réserve qui dégage la barre d'onglets, fixée au bas de
                 l'écran. Elle était posée sur `<main>` — mais le pied de page
                 vient après, et sa dernière ligne (« Gratuit pour les clients ·
                 Abonnement mensuel… ») restait sous la barre sur tous les
                 écrans, définitivement hors de portée. La réserve appartient
                 donc à ce qui ferme le document, pas au contenu. --}}
            <div aria-hidden="true" class="h-[4.75rem] shrink-0 md:hidden"></div>

            @include('layouts.bottom-nav')

            {{-- L'assistant aide les clients et les prestataires. Sur les
                 écrans d'administration il ne sert personne, et sa bulle
                 flottante recouvrait les chiffres du tableau de bord — au
                 point de me faire croire à des valeurs manquantes. --}}
            @if ($assistant && ! auth()->user()?->isAdmin())
                @include('partials.chatbot-widget')
            @endif
        </div>
    </body>
</html>
