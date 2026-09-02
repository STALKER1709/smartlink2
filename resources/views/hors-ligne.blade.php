{{--
    La page servie par le service worker quand le réseau ne répond pas.

    Elle est volontairement autonome : aucune police distante, aucune feuille
    de style, aucun script. C'est la seule page du site dont on sait d'avance
    qu'elle s'affichera sans réseau — tout ce qu'elle irait chercher ailleurs
    serait précisément ce qui manque au moment où elle sert.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#005f48">
    <title>{{ __('pwa.offline_title') }} · {{ config('app.name', 'SmartLink') }}</title>
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
        .carte { max-width: 22rem; }
        svg { color: #005f48; }
        h1 { margin: 1.5rem 0 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -0.01em; }
        p { margin: 0.75rem 0 0; line-height: 1.6; color: #414942; }
        button {
            margin-top: 1.75rem;
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

        @media (prefers-color-scheme: dark) {
            :root { color-scheme: dark; }
            body { background: #111413; color: #e1e3e1; }
            svg { color: #79d8b7; }
            p { color: #bfc9c4; }
            button { background: #79d8b7; color: #00382a; }
            button:hover { background: #95f5d2; }
        }
    </style>
</head>
<body>
    <div class="carte">
        <svg width="64" height="64" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M12.5 19.5 19.5 12.5" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            <circle cx="9" cy="23" r="5.25" stroke="currentColor" stroke-width="3"/>
            <circle cx="23" cy="9" r="5.25" stroke="currentColor" stroke-width="3"/>
        </svg>

        <h1>{{ __('pwa.offline_heading') }}</h1>
        <p>{{ __('pwa.offline_body') }}</p>

        <button type="button" onclick="location.reload()">{{ __('pwa.offline_retry') }}</button>
    </div>
</body>
</html>
