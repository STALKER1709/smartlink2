<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Le manifeste d'installation.
 *
 * Servi par l'application plutôt que déposé en fichier statique pour deux
 * raisons : le type MIME est garanti — un manifeste servi en
 * `application/octet-stream` est ignoré sans message — et les libellés suivent
 * la langue de l'application au lieu d'être figés en français dans un fichier.
 */
class WebManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $icones = [
            ['src' => asset('images/icone-192.png'), 'sizes' => '192x192'],
            ['src' => asset('images/icone-512.png'), 'sizes' => '512x512'],
        ];

        return response()->json([
            'name' => __('pwa.name'),
            'short_name' => config('app.name', 'SmartLink'),
            'description' => __('seo.default_description'),
            'lang' => app()->getLocale(),
            'dir' => 'ltr',

            /*
             * `url('/')` rend la racine sans barre finale. La portée est
             * comparée comme un préfixe d'URL : sans la barre, un domaine
             * voisin dont le nom commence pareil tomberait dedans.
             */
            'start_url' => $racine = rtrim(url('/'), '/').'/',
            'scope' => $racine,
            'display' => 'standalone',
            'orientation' => 'portrait',

            // Le vert de marque : c'est la couleur de la barre système une
            // fois l'application ouverte depuis l'écran d'accueil.
            'theme_color' => '#005538',
            // L'écran affiché le temps du premier rendu. Il doit être clair
            // comme le fond du site, sinon l'ouverture passe par un éclair
            // sombre à chaque fois.
            'background_color' => '#FFFFFF',

            /*
             * `any` et `maskable` pointent sur les mêmes fichiers : le fond
             * est plein bord et le tracé tient dans les 80 % centraux, donc
             * l'icône supporte d'être rognée en cercle ou en goutte par le
             * lanceur. Sans une entrée `maskable`, Android ajoute lui-même un
             * cadre blanc autour de l'icône.
             */
            'icons' => [
                ...array_map(fn (array $i) => [...$i, 'type' => 'image/png', 'purpose' => 'any'], $icones),
                ...array_map(fn (array $i) => [...$i, 'type' => 'image/png', 'purpose' => 'maskable'], $icones),
            ],

            /*
             * Les raccourcis de l'appui long sur l'icône. Uniquement des pages
             * publiques : un raccourci vers un espace privé n'ouvrirait qu'un
             * écran de connexion.
             */
            'shortcuts' => [
                [
                    'name' => __('pwa.shortcut_services'),
                    'url' => route('services.index'),
                    'icons' => [['src' => asset('images/icone-192.png'), 'sizes' => '192x192']],
                ],
                [
                    'name' => __('pwa.shortcut_providers'),
                    'url' => route('providers.index'),
                    'icons' => [['src' => asset('images/icone-192.png'), 'sizes' => '192x192']],
                ],
            ],

            'categories' => ['business', 'lifestyle', 'productivity'],
        ], 200, [
            'Content-Type' => 'application/manifest+json',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
