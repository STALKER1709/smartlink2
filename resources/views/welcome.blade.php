<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-50 text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col items-center justify-center px-4 text-center">
            <h1 class="text-3xl font-bold text-indigo-600">{{ config('app.name') }}</h1>
            <p class="mt-2 text-gray-600">La mise en relation directe entre prestataires de services et clients au Cameroun.</p>

            <div class="mt-6 flex gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-white">Mon tableau de bord</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-md border border-gray-300 px-4 py-2">Se connecter</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-white">S'inscrire</a>
                @endauth
            </div>
        </div>
    </body>
</html>
