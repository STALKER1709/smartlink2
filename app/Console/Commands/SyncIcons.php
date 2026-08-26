<?php

namespace App\Console\Commands;

use App\Support\NavigationLinks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Régénère la liste des icônes demandées à Google Fonts.
 *
 * La police complète pèse 1,1 Mo pour une cinquantaine d'icônes utilisées. On
 * ne demande donc que le sous-ensemble — mais un sous-ensemble se périme dès
 * qu'une vue ajoute une icône, et le symptôme est discret : le nom de la
 * ligature s'affiche en toutes lettres à la place du dessin.
 *
 * Cette commande relit les vues et réécrit config/icons.php.
 * `tests/Feature/IconSubsetTest.php` échoue si les deux divergent.
 *
 * ⚠️ Une ligature construite dynamiquement — `{{ $icone }}` dans une boucle —
 * reste invisible à l'analyse. Écris le nom en clair dans le balisage, ou
 * passe-le par une propriété `icon="..."`, qui, elle, est reconnue.
 */
class SyncIcons extends Command
{
    protected $signature = 'icons:sync {--check : Échoue si la liste est périmée, sans rien réécrire}';

    protected $description = 'Régénère la liste des icônes Material Symbols utilisées par les vues';

    public function handle(): int
    {
        $used = self::scan();
        $configured = config('icons.names', []);

        if ($used === $configured) {
            $this->info(count($used).' icônes, liste à jour.');

            return self::SUCCESS;
        }

        $manquantes = array_diff($used, $configured);
        $superflues = array_diff($configured, $used);

        if ($manquantes !== []) {
            $this->warn('Absentes de la liste (elles s\'afficheront en toutes lettres) : '.implode(', ', $manquantes));
        }

        if ($superflues !== []) {
            $this->line('Demandées sans être utilisées : '.implode(', ', $superflues));
        }

        if ($this->option('check')) {
            $this->error('Liste périmée. Lancer php artisan icons:sync.');

            return self::FAILURE;
        }

        File::put(config_path('icons.php'), self::render($used));
        $this->info(count($used).' icônes écrites dans config/icons.php.');

        return self::SUCCESS;
    }

    /**
     * Les ligatures réellement présentes dans les vues.
     *
     * @return array<int, string>
     */
    public static function scan(): array
    {
        $noms = [];

        // La barre d'onglets rend ses icônes par une expression : aucune de
        // ses ligatures n'apparaît littéralement dans le balisage. La table les
        // énumère elle-même, ce qui vaut mieux qu'une expression régulière
        // taillée sur sa forme du jour.
        $noms = NavigationLinks::icones();

        foreach (File::allFiles(resource_path('views')) as $fichier) {
            $contenu = $fichier->getContents();

            preg_match_all('/material-symbols-outlined[^>]*>\s*([a-z0-9_]+)\s*</', $contenu, $m);
            $noms = array_merge($noms, $m[1]);

            // Les icônes passées en propriété de composant — <x-empty-state
            // icon="person_search"> — n'apparaissent pas dans le balisage sous
            // forme de ligature. Sans cette passe, elles disparaissaient du
            // sous-ensemble et s'affichaient en toutes lettres.
            preg_match_all('/\bicon=(?:"|\x27)([a-z0-9_]+)(?:"|\x27)/', $contenu, $m);
            $noms = array_merge($noms, $m[1]);

            // Le composant de catégorie tient sa propre table de correspondance :
            // ces ligatures n'apparaissent nulle part ailleurs dans le balisage.
            if ($fichier->getFilename() === 'category-icon.blade.php') {
                preg_match_all("/=>\s*'([a-z0-9_]+)'/", $contenu, $m);
                $noms = array_merge($noms, $m[1], ['handyman']);
            }
        }

        $noms = array_values(array_unique(array_filter($noms)));
        sort($noms);

        return $noms;
    }

    /**
     * @param  array<int, string>  $noms
     */
    private static function render(array $noms): string
    {
        $lignes = implode("\n", array_map(fn (string $n) => "        '{$n}',", $noms));

        return <<<PHP
            <?php

            /*
             * Icônes Material Symbols demandées à Google Fonts.
             *
             * Fichier généré : ne pas modifier à la main.
             * Régénérer avec `php artisan icons:sync` après avoir ajouté une icône
             * dans une vue, sinon elle s'affichera en toutes lettres.
             */

            return [

                'names' => [
            {$lignes}
                ],

            ];

            PHP;
    }
}
