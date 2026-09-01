{{--
    La page des 500, volontairement autonome — comme `hors-ligne.blade.php`,
    et pour la même raison retournée.

    Une 500 veut dire que quelque chose vient de casser, et on ne sait pas
    quoi. La base peut être injoignable, le manifeste de Vite absent, une
    extension manquante. Tout ce que cette page irait chercher ailleurs est
    précisément ce qui pourrait manquer au moment où elle sert : `@vite` lève
    si le manifeste n'est pas là, `@auth` ouvre la session — donc la base,
    quand c'est elle qui est tombée. Une page d'erreur qui échoue à s'afficher
    laisse le visiteur devant l'écran blanc du serveur, sans même le nom du
    site.

    Elle n'a donc ni feuille de style, ni script, ni composant, ni session :
    les couleurs de la charte y sont écrites en clair, comme sur la page
    hors-ligne, et `CharteTest` porte les deux à la même exception.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#005f48">
    <title>{{ __('Erreur serveur') }} · {{ config('app.name', 'SmartLink') }}</title>
    <style>
        :root { color-scheme: light; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: #F7F9F8;
            color: #1A1C1B;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            text-align: center;
        }
        .carte { max-width: 24rem; }
        svg { color: #005f48; }
        h1 { margin: 1.5rem 0 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -0.01em; }
        p { margin: 0.75rem 0 0; line-height: 1.6; color: #414942; }
        .actions { margin-top: 1.75rem; display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: center; }
        button, a.bouton {
            padding: 0.75rem 1.5rem;
            border: 0;
            border-radius: 9999px;
            background: #005f48;
            color: #FFFFFF;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        button:hover, a.bouton:hover { background: #00432C; }
        a.discret {
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            border: 1px solid #C2C9C2;
            color: #414942;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        a.discret:hover { background: #ECEFEC; }
    </style>
</head>
<body>
    <div class="carte">
        {{-- Un triangle d'alerte, dans le vert du site : ce n'est pas une
             faute du visiteur, la page n'a pas à le lui faire craindre. --}}
        <svg width="64" height="64" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M16 4.5 29 27H3L16 4.5Z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/>
            <path d="M16 13v6" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            <circle cx="16" cy="23" r="1.6" fill="currentColor"/>
        </svg>

        <h1>{{ __("Quelque chose s'est cassé de notre côté") }}</h1>
        <p>{{ __("Ce n'est pas vous. L'incident est enregistré. Réessayez dans un instant : la plupart de ces erreurs ne durent pas.") }}</p>

        <div class="actions">
            <button type="button" onclick="location.reload()">{{ __('Réessayer') }}</button>
            <a class="discret" href="{{ url('/') }}">{{ __("Retour à l'accueil") }}</a>
        </div>
    </div>
</body>
</html>
