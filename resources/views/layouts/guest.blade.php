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
        {{-- Seules les icônes réellement utilisées sont demandées : la police
             complète pèse 1,1 Mo, ce sous-ensemble 20 Ko. « display=block »
             plutôt que « swap » évite d'afficher le nom de la ligature
             (« location_on », « arrow_forward ») pendant le chargement. --}}
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&icon_names=ac_unit,arrow_forward,auto_awesome,bolt,cake,carpenter,chat,check_circle,checkroom,close,computer,content_cut,directions_car,diversity_3,done_all,electric_bolt,expand_more,format_paint,forum,foundation,grid_view,handyman,home_repair_service,inbox,local_fire_department,local_shipping,location_on,lock,menu,menu_book,music_note,notifications,pending_actions,person_search,photo_camera,plumbing,receipt_long,reviews,search,search_off,send,shield,shopping_bag,smart_toy,smartphone,spa,star,tune,tv,verified,water_drop,yard&display=block" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-on-surface antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-surface-container-low">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-primary" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-surface-container-lowest border border-outline-variant overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
