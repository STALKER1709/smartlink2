<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Models\ServiceImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pose les photographies déposées dans `design/photos/` sur les services.
 *
 * Les illustrations générées (`database/seeders/data/images/`) sont un repli :
 * dès qu'une photographie existe pour une catégorie, elle la remplace. La
 * commande existe pour que cette bascule ne demande ni migration ni retouche
 * de vue — seulement des fichiers déposés et une ligne de commande.
 */
class ImportPhotos extends Command
{
    protected $signature = 'photos:import
        {--list : Affiche les catégories reconnues et ce qui a déjà été déposé}
        {--dry-run : Montre ce qui serait fait, sans rien écrire}';

    protected $description = 'Importe les photographies de design/photos vers les services';

    public function handle(): int
    {
        $dossier = base_path('design/photos');
        $table = $this->categories();

        if ($table === []) {
            $this->error('categories.json introuvable : lancez d\'abord node database/seeders/data/images/generate.mjs');

            return self::FAILURE;
        }

        if ($this->option('list')) {
            return $this->lister($dossier, $table);
        }

        $fichiers = collect(File::glob($dossier.'/*.{jpg,jpeg,png,webp}', GLOB_BRACE));

        if ($fichiers->isEmpty()) {
            $this->warn('Aucune photographie dans design/photos.');
            $this->line('Déposez vos fichiers puis relancez. Voir design/photos/README.md.');

            return self::SUCCESS;
        }

        $sec = (bool) $this->option('dry-run');
        $poses = 0;
        $ignores = [];

        foreach ($fichiers as $chemin) {
            $cle = Str::of(basename($chemin))->beforeLast('.')->beforeLast('-')->toString();
            $categories = array_keys($table, $cle, true);

            if ($categories === []) {
                $ignores[] = basename($chemin).' (clé « '.$cle.' » inconnue)';

                continue;
            }

            $services = Service::query()
                ->whereHas('category', fn ($q) => $q->whereIn('name', $categories))
                ->get();

            if ($services->isEmpty()) {
                $ignores[] = basename($chemin).' (aucun service dans '.implode(', ', $categories).')';

                continue;
            }

            $destination = 'services/photos/'.basename($chemin);

            if (! $sec) {
                Storage::disk(media_disk())->put($destination, File::get($chemin));

                foreach ($services as $service) {
                    /*
                     * Une photographie remplace l'illustration, elle ne s'y
                     * ajoute pas : deux couvertures pour un même service
                     * laisseraient le hasard décider laquelle paraît. Seuls
                     * les visuels de remplissage sont retirés — jamais les
                     * photos qu'un prestataire a déposées lui-même.
                     */
                    $service->images()
                        ->where(fn ($q) => $q
                            ->where('path', 'like', 'services/demo/%')
                            ->orWhere('path', 'like', 'services/placeholder%'))
                        ->delete();

                    ServiceImage::updateOrCreate(
                        ['service_id' => $service->id, 'path' => $destination],
                        ['position' => 0],
                    );
                }
            }

            $this->line(sprintf(
                '  %s → %d service%s (%s)',
                basename($chemin),
                $services->count(),
                $services->count() > 1 ? 's' : '',
                implode(', ', $categories),
            ));
            $poses++;
        }

        foreach ($ignores as $ignore) {
            $this->warn('  ignoré : '.$ignore);
        }

        $this->newLine();
        $this->info($sec
            ? $poses.' photographie(s) seraient posées. Relancez sans --dry-run.'
            : $poses.' photographie(s) posées sur le disque « '.media_disk().' ».');

        return self::SUCCESS;
    }

    private function lister(string $dossier, array $table): int
    {
        $lignes = [];

        foreach (array_unique(array_values($table)) as $cle) {
            $deposees = count(File::glob($dossier.'/'.$cle.'-*.{jpg,jpeg,png,webp}', GLOB_BRACE));
            $lignes[] = [
                $cle,
                implode(', ', array_keys($table, $cle, true)),
                $deposees > 0 ? (string) $deposees : '—',
            ];
        }

        $this->table(['Clé de fichier', 'Catégorie', 'Photos déposées'], $lignes);

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function categories(): array
    {
        $chemin = base_path('database/seeders/data/images/categories.json');

        return File::exists($chemin)
            ? (array) json_decode(File::get($chemin), true)
            : [];
    }
}
