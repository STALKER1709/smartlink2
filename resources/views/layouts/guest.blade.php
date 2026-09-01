<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('partials.theme-head')

        <x-seo :titre="$titre" :description="$description" :indexable="$indexable" />

        @include('partials.pwa-head')        {{-- Les deux fichiers que toute page française lit, demandés avant que
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
        <div class="flex min-h-screen flex-col bg-surface-container-low">
            <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-4 py-10">
                <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center mx-auto flex items-center gap-2.5 text-primary">
                    <x-application-logo class="h-9 w-9" />
                    <span class="font-headline-md text-headline-md font-extrabold tracking-tight text-on-surface">SmartLink</span>
                </a>

                <div class="mt-8 rounded-xl border border-outline-variant bg-surface-container-lowest p-6 shadow-elevation-1 sm:p-8">
                    {{ $slot }}
                </div>

                {{-- Les écrans d'authentification se voient aussi de nuit, et
                     l'on n'y est par définition pas connecté : le réglage doit
                     être atteignable sans compte. --}}
                <x-theme-switch class="mx-auto mt-6" />

                <a href="{{ route('home') }}"
                   class="group mx-auto mt-4 inline-flex min-h-11 items-center gap-1.5 rounded-full px-4 text-label-md text-on-surface-variant transition-colors hover:bg-surface-container hover:text-on-surface">
                    <x-icon name="arrow_back" size="sm" class="transition-transform duration-150 group-hover:-translate-x-0.5" />
                    {{ __("Retour à l'accueil") }}
                </a>

                {{-- Les écrans d'authentification n'ont pas de pied de page :
                     ces liens y seraient introuvables autrement, alors que
                     c'est précisément là qu'on s'engage. --}}
                {{-- Les trois liens faisaient 16 px de haut, mesure prise dans le
                     navigateur : la hauteur de leur texte, sans rien autour. Les
                     séparateurs « · » disparaissent avec — ils ne servaient qu'à
                     écarter des cibles trop serrées. --}}
                <nav class="mx-auto mt-1 flex flex-wrap justify-center gap-x-1 text-label-sm text-on-surface-variant">
                    <a href="{{ route('legal.terms') }}" class="inline-flex min-h-11 items-center rounded-full px-3 transition-colors hover:bg-surface-container hover:text-primary">{{ __("Conditions générales") }}</a>
                    <a href="{{ route('legal.privacy') }}" class="inline-flex min-h-11 items-center rounded-full px-3 transition-colors hover:bg-surface-container hover:text-primary">{{ __("Confidentialité") }}</a>
                    <a href="{{ route('legal.notice') }}" class="inline-flex min-h-11 items-center rounded-full px-3 transition-colors hover:bg-surface-container hover:text-primary">{{ __("Mentions légales") }}</a>
                </nav>
            </div>
        </div>
    </body>
</html>
