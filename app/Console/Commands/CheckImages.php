<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Vérifie la table des photographies (`config/imagery.php`).
 *
 * Deux défauts s'y installent en silence. Une URL qui ne répond plus : la
 * balise s'efface d'elle-même et le métier retombe sur son illustration, sans
 * que rien ne le signale — la page reste correcte et la photo a disparu. Et
 * une URL sans mention d'auteur : presque toutes les licences libres l'exigent,
 * et son absence ne se voit que le jour où l'auteur la réclame.
 *
 * La commande demande le réseau ; elle est faite pour être lancée depuis un
 * poste ou un serveur qui l'a, pas depuis une chaîne d'intégration muette.
 */
class CheckImages extends Command
{
    protected $signature = 'images:check
        {--mentions-seules : Vérifie les mentions d\'auteur, sans toucher au réseau}';

    protected $description = 'Vérifie les photographies déclarées dans config/imagery.php';

    public function handle(): int
    {
        $entrees = collect(config('imagery.categories', []))
            ->put('(bandeau prestataires)', config('imagery.cta'))
            ->filter(fn ($entree) => is_array($entree) && ! empty($entree['url']));

        if ($entrees->isEmpty()) {
            $this->info('Aucune photographie déclarée : les illustrations dessinées font le travail.');
            $this->line('Pour en ajouter : node design/photos/fetch.mjs --par 1, puis php artisan photos:import.');

            return self::SUCCESS;
        }

        if (! config('imagery.enabled')) {
            $this->warn('REMOTE_IMAGES est à faux : la table est remplie mais rien ne s\'affiche.');
        }

        $reseau = ! $this->option('mentions-seules');
        $defauts = 0;

        foreach ($entrees as $intitule => $entree) {
            $problemes = [];

            foreach (['auteur', 'licence'] as $champ) {
                if (empty($entree[$champ])) {
                    $problemes[] = 'sans '.$champ;
                }
            }

            if ($reseau) {
                $etat = $this->joignable($entree['url']);

                if ($etat !== null) {
                    $problemes[] = $etat;
                }
            }

            if ($problemes === []) {
                $this->line('  <fg=green>OK</>   '.$intitule);

                continue;
            }

            $defauts++;
            $this->line('  <fg=red>KO</>   '.$intitule.' — '.implode(', ', $problemes));
        }

        $this->newLine();

        if ($defauts > 0) {
            $this->error($defauts.' entrée(s) à corriger dans config/imagery.php.');

            return self::FAILURE;
        }

        $this->info($entrees->count().' photographie(s) vérifiée(s).');

        return self::SUCCESS;
    }

    /**
     * Le problème rencontré, ou null si la photo est là.
     */
    private function joignable(string $url): ?string
    {
        // Un chemin sur le disque de médias se vérifie sans réseau : c'est le
        // cas des photos rapatriées par `photos:import`.
        if (! str_starts_with($url, 'http')) {
            return Storage::disk(media_disk())->exists($url) ? null : 'absente du disque de médias';
        }

        try {
            $reponse = Http::timeout(15)->withHeaders(['User-Agent' => 'SmartLink/1.0'])->get($url);
        } catch (\Throwable $e) {
            return 'injoignable ('.class_basename($e).')';
        }

        if (! $reponse->successful()) {
            return 'réponse '.$reponse->status();
        }

        $type = $reponse->header('Content-Type');

        return str_starts_with((string) $type, 'image/') ? null : 'ce n\'est pas une image ('.($type ?: 'type inconnu').')';
    }
}
