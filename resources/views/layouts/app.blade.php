<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700;800&family=Source+Sans+3:wght@400;600&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet" />
        @include('partials.icon-font')

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-on-surface antialiased">
        <div class="min-h-screen bg-surface">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-surface-container-lowest border-b border-outline-variant">
                    <div class="max-w-container mx-auto py-6 px-margin-mobile md:px-margin-desktop">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            @include('partials.subscription-banner')

            @include('partials.flash-messages')

            <!-- Page Content -->
            {{-- La marge basse dégage la barre d'onglets, qui est fixée : sans
                 elle, le dernier bloc de chaque page passe dessous. --}}
            <main class="pb-[4.75rem] md:pb-0">
                {{ $slot }}
            </main>

            @if ($piedDePage)
                @include('layouts.footer')
            @endif

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
