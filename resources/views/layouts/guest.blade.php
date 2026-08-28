<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <x-seo :titre="$titre" :description="$description" :indexable="$indexable" />

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700;800&family=Source+Sans+3:wght@400;600&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet" />
        @include('partials.icon-font')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-on-surface antialiased">
        <div class="flex min-h-screen flex-col bg-surface-container-low">
            <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-4 py-10">
                <a href="{{ route('home') }}" class="mx-auto flex items-center gap-2.5 text-primary">
                    <x-application-logo class="h-9 w-9" />
                    <span class="font-headline-lg text-2xl font-extrabold tracking-tight text-on-surface">SmartLink</span>
                </a>

                <div class="mt-8 rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm sm:p-8">
                    {{ $slot }}
                </div>

                <a href="{{ route('home') }}" class="mx-auto mt-6 flex items-center gap-1 text-sm text-on-surface-variant hover:text-on-surface">
                    <span class="material-symbols-outlined text-base">arrow_back</span>
                    Retour à l'accueil
                </a>

                {{-- Les écrans d'authentification n'ont pas de pied de page :
                     ces liens y seraient introuvables autrement, alors que
                     c'est précisément là qu'on s'engage. --}}
                <p class="mx-auto mt-4 flex flex-wrap justify-center gap-x-3 gap-y-1 text-xs text-on-surface-variant">
                    <a href="{{ route('legal.terms') }}" class="hover:text-primary">Conditions générales</a>
                    <span aria-hidden="true">·</span>
                    <a href="{{ route('legal.privacy') }}" class="hover:text-primary">Confidentialité</a>
                    <span aria-hidden="true">·</span>
                    <a href="{{ route('legal.notice') }}" class="hover:text-primary">Mentions légales</a>
                </p>
            </div>
        </div>
    </body>
</html>
