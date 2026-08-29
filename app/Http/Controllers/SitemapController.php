<?php

namespace App\Http\Controllers;

use App\Models\ProviderProfile;
use App\Models\Service;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Le plan du site et le fichier des robots.
 *
 * Les deux sont produits par l'application plutôt que déposés en fichiers
 * statiques, pour une raison unique mais suffisante : la directive `Sitemap`
 * de `robots.txt` exige une URL **absolue**. Écrite en dur, elle serait fausse
 * sur chaque environnement sauf un — le domaine de production, les
 * déploiements de prévisualisation et l'installation locale ne partagent aucun
 * hôte. `url()` la compose à partir d'`APP_URL`, et elle reste juste partout.
 */
class SitemapController extends Controller
{
    /**
     * Un plan de site accepte 50 000 URL. On s'arrête bien avant : au-delà,
     * la réponse serait construite en mémoire à chaque passage d'un robot, et
     * il faudra de toute façon découper en plusieurs fichiers. Le jour où
     * cette borne est atteinte, c'est un index de plans qu'il faut écrire.
     */
    private const MAX_PAR_TYPE = 10000;

    /**
     * Une heure de mémoire.
     *
     * La page est publique, sans authentification, et son coût croît avec le
     * catalogue : deux requêtes qui balaient les services et les profils, puis
     * une chaîne construite en mémoire, à chaque passage. Un robot poli passe
     * une fois par jour ; n'importe qui d'autre peut la marteler, et c'est la
     * base qui paie.
     *
     * Une heure ne coûte rien en référencement — les moteurs reviennent sur
     * plusieurs jours, pas à la minute.
     */
    private const DUREE_DU_CACHE_EN_MINUTES = 60;

    public function index(): Response
    {
        /*
         * La clé porte la racine du site : `trustHosts` accepte plusieurs noms
         * légitimes — le domaine et, sur Vercel, l'adresse de déploiement — et
         * le plan contient des URL absolues. Une clé unique servirait à l'un
         * le plan de l'autre.
         */
        $xml = Cache::remember(
            'sitemap:'.sha1(url('/')),
            now()->addMinutes(self::DUREE_DU_CACHE_EN_MINUTES),
            fn (): string => $this->render($this->urls()),
        );

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            // Le même répit pour les intermédiaires que pour nous.
            'Cache-Control' => 'public, max-age='.(self::DUREE_DU_CACHE_EN_MINUTES * 60),
        ]);
    }

    /**
     * @return list<array{loc: string, lastmod: Carbon|null}>
     */
    private function urls(): array
    {
        $urls = [];

        // Les pages fixes. `changefreq` et `priority` ne sont plus lus par
        // Google depuis 2023 ; seuls l'URL et la date de dernière
        // modification comptent, et une date inventée sur une page fixe
        // vaudrait moins que pas de date du tout.
        foreach (['home', 'services.index', 'providers.index', 'help.index',
            'legal.terms', 'legal.notice', 'legal.privacy'] as $route) {
            $urls[] = ['loc' => route($route), 'lastmod' => null];
        }

        /*
         * Ne sont listés que les services qu'un visiteur non connecté peut
         * réellement ouvrir. La fiche d'un prestataire retiré des recherches
         * — abonnement échu, plafond mensuel atteint, compte supprimé —
         * répond 404 : l'annoncer aux robots ne ferait que collectionner des
         * erreurs d'exploration.
         */
        $services = Service::query()
            ->active()
            ->fromListedProvider()
            ->select(['slug', 'updated_at'])
            ->orderByDesc('updated_at')
            ->limit(self::MAX_PAR_TYPE)
            ->get();

        foreach ($services as $service) {
            $urls[] = [
                'loc' => route('services.show', $service->slug),
                'lastmod' => $service->updated_at,
            ];
        }

        $profiles = ProviderProfile::query()
            ->listed()
            ->whereHas('user')
            ->select(['id', 'updated_at'])
            ->orderByDesc('updated_at')
            ->limit(self::MAX_PAR_TYPE)
            ->get();

        foreach ($profiles as $profile) {
            $urls[] = [
                'loc' => route('providers.show', $profile->id),
                'lastmod' => $profile->updated_at,
            ];
        }

        return $urls;
    }

    /**
     * @param  list<array{loc: string, lastmod: Carbon|null}>  $urls
     */
    private function render(array $urls): string
    {
        $lignes = ['<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($urls as $url) {
            $lignes[] = '  <url>';
            $lignes[] = '    <loc>'.htmlspecialchars($url['loc'], ENT_XML1).'</loc>';

            if ($url['lastmod'] instanceof Carbon) {
                // W3C Datetime, ce qu'attend la spécification des plans de site.
                $lignes[] = '    <lastmod>'.$url['lastmod']->toAtomString().'</lastmod>';
            }

            $lignes[] = '  </url>';
        }

        $lignes[] = '</urlset>';

        return implode("\n", $lignes)."\n";
    }

    /**
     * Le fichier des robots, avec la seule ligne qui ne pouvait pas être
     * écrite en dur : l'adresse absolue du plan du site.
     */
    public function robots(): Response
    {
        return response(view('sitemap.robots')->render(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
