<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Va chercher l'auteur et la licence des photographies de Wikimedia Commons.
 *
 * Une photo sous licence libre n'est pas une photo sans obligation : presque
 * toutes réclament que la page qui l'affiche nomme son auteur et sa licence.
 * Ces deux mentions sont sur la page du fichier, et les recopier à la main
 * pour trente et une entrées, c'est trente et une occasions d'en oublier une —
 * un oubli qui ne se voit que le jour où l'auteur le réclame.
 *
 * La commande demande le réseau. Elle ne réécrit pas `config/imagery.php` :
 * ce fichier est versionné, et une commande qui remplace un fichier versionné
 * sans qu'on l'ait relu rend un mauvais service. Elle rend le bloc, à coller.
 */
class FetchImageCredits extends Command
{
    protected $signature = 'images:credits';

    protected $description = 'Complète les mentions d\'auteur des photos Wikimedia Commons de config/imagery.php';

    public function handle(): int
    {
        $entrees = collect(config('imagery.categories', []))
            ->filter(fn ($entree) => is_array($entree) && ! empty($entree['url']));

        if ($entrees->isEmpty()) {
            $this->info('Aucune photographie déclarée dans config/imagery.php.');

            return self::SUCCESS;
        }

        $lignes = [];
        $echecs = 0;

        foreach ($entrees as $categorie => $entree) {
            $fichier = $this->fichierCommons($entree['url']);

            if ($fichier === null) {
                $this->line('  <fg=yellow>–</>    '.$categorie.' : pas une URL Wikimedia Commons, laissée telle quelle');
                $lignes[$categorie] = $this->bloc($categorie, $entree);

                continue;
            }

            [$mentions, $probleme] = $this->mentions($fichier);

            if ($mentions === null) {
                $echecs++;
                // Le motif est nommé : « injoignable » et « introuvable » ne
                // se corrigent pas de la même façon, et la commande a déjà
                // menti une fois en confondant un réseau muet avec un fichier
                // absent.
                $this->line('  <fg=red>KO</>   '.$categorie.' : '.$fichier.' — '.$probleme);
                $lignes[$categorie] = $this->bloc($categorie, $entree);

                continue;
            }

            $this->line('  <fg=green>OK</>   '.$categorie.' — '.$mentions['auteur'].' — '.$mentions['licence']);
            $lignes[$categorie] = $this->bloc($categorie, array_merge($entree, $mentions));
        }

        ksort($lignes);

        $this->newLine();
        $this->line('À coller dans la table « categories » de config/imagery.php :');
        $this->newLine();
        $this->line(implode("\n", $lignes));
        $this->newLine();
        $this->line('Puis : php artisan images:check, et REMOTE_IMAGES=true quand les photos vous conviennent.');

        return $echecs > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Le titre du fichier derrière une URL `Special:FilePath`, ou null.
     */
    private function fichierCommons(string $url): ?string
    {
        // Délimiteur `~` : le motif contient un `#`, qui aurait refermé un
        // délimiteur `#` au milieu de la classe de caractères.
        if (! preg_match('~commons\.wikimedia\.org/wiki/Special:FilePath/([^?#]+)~', $url, $trouve)) {
            return null;
        }

        return urldecode($trouve[1]);
    }

    /**
     * @return array{0: array{auteur: string, licence: string}|null, 1: ?string}
     */
    private function mentions(string $fichier): array
    {
        try {
            $reponse = Http::timeout(20)
                ->withHeaders(['User-Agent' => 'SmartLink/1.0 (images:credits)'])
                ->get('https://commons.wikimedia.org/w/api.php', [
                    'action' => 'query',
                    'format' => 'json',
                    'titles' => 'File:'.$fichier,
                    'prop' => 'imageinfo',
                    'iiprop' => 'extmetadata',
                ]);
        } catch (\Throwable $e) {
            return [null, 'Commons injoignable ('.class_basename($e).')'];
        }

        if (! $reponse->successful()) {
            return [null, 'Commons a répondu '.$reponse->status()];
        }

        $pages = $reponse->json('query.pages') ?? [];
        $meta = data_get(array_values($pages), '0.imageinfo.0.extmetadata');

        if (! is_array($meta)) {
            return [null, 'fichier absent de Commons, ou sans métadonnées'];
        }

        // `Artist` arrive en HTML : c'est un lien vers la page de l'auteur.
        $auteur = trim(html_entity_decode(strip_tags((string) data_get($meta, 'Artist.value', ''))));
        $licence = trim((string) data_get($meta, 'LicenseShortName.value', ''));

        if ($auteur === '' && $licence === '') {
            return [null, 'ni auteur ni licence dans les métadonnées'];
        }

        return [[
            'auteur' => $auteur !== '' ? $auteur : 'auteur non nommé sur la page source',
            'licence' => $licence !== '' ? $licence : 'licence à relever sur la page source',
        ], null];
    }

    /**
     * @param  array<string, string|null>  $entree
     */
    private function bloc(string $categorie, array $entree): string
    {
        return sprintf(
            "        '%s' => [\n            'url' => %s,\n            'auteur' => %s,\n            'licence' => %s,\n            'source' => %s,\n        ],",
            str_replace("'", "\\'", $categorie),
            $this->php($entree['url'] ?? null),
            $this->php($entree['auteur'] ?? null),
            $this->php($entree['licence'] ?? null),
            $this->php($entree['source'] ?? null),
        );
    }

    private function php(?string $valeur): string
    {
        return $valeur === null || $valeur === '' ? 'null' : "'".str_replace("'", "\\'", $valeur)."'";
    }
}
