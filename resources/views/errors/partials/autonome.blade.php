{{--
    Le document complet des pages d'erreur qui ne peuvent dépendre de rien.

    Les 500 et 503 servent au moment où l'on ne sait pas ce qui est cassé, ou
    bien pendant qu'on le répare. Tout ce qu'elles iraient chercher ailleurs
    fait partie de ce qui peut manquer à cet instant : `@vite` lève si le
    manifeste n'est pas là, `@auth` ouvre la session — donc la base, quand
    c'est elle qui est tombée. Une page d'erreur qui échoue à s'afficher
    laisse le visiteur devant l'écran blanc du serveur, sans même le nom du
    site.

    Ni feuille de style, ni script, ni composant, ni session, ni route : les
    couleurs de la charte y sont écrites en clair, comme sur la page
    hors-ligne, et `CharteTest` porte les deux à la même exception.

    Attend : $titre, $intitule, $texte, $dessin, et éventuellement $reessayer.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#005f48">
    <title>{{ $titre }} · {{ config('app.name', 'SmartLink') }}</title>
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
        .marque {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            color: #005f48;
            font-weight: 800;
            font-size: 1.05rem;
            letter-spacing: -0.01em;
            text-decoration: none;
        }
        .marque span { color: #1A1C1B; }
        .dessin { margin-top: 2rem; color: #005f48; }
        h1 { margin: 1.5rem 0 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -0.01em; }
        p { margin: 0.75rem 0 0; line-height: 1.6; color: #414942; }
        .actions { margin-top: 1.75rem; display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: center; }
        button {
            padding: 0.75rem 1.5rem;
            border: 0;
            border-radius: 9999px;
            background: #005f48;
            color: #FFFFFF;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover { background: #00432C; }
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
        {{-- Le nom du site en premier : sur une page d'erreur, savoir où l'on
             est encore vaut mieux que savoir ce qui a échoué. --}}
        <a class="marque" href="{{ url('/') }}">
            <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M12.5 19.5 19.5 12.5" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                <circle cx="9" cy="23" r="5.25" stroke="currentColor" stroke-width="3"/>
                <circle cx="23" cy="9" r="5.25" stroke="currentColor" stroke-width="3"/>
            </svg>
            <span>SmartLink</span>
        </a>

        <div class="dessin">{!! $dessin !!}</div>

        <h1>{{ $intitule }}</h1>
        <p>{{ $texte }}</p>

        <div class="actions">
            @if ($reessayer ?? true)
                <button type="button" onclick="location.reload()">{{ __('Réessayer') }}</button>
            @endif
            <a class="discret" href="{{ url('/') }}">{{ __("Retour à l'accueil") }}</a>
        </div>
    </div>
</body>
</html>
