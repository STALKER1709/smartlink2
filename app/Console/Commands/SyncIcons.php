<?php

namespace App\Console\Commands;

use App\Support\NavigationLinks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Régénère la liste des icônes, et le fichier de police qui la sert.
 *
 * La police complète pèse 1,1 Mo pour une cinquantaine d'icônes utilisées. On
 * n'en sert donc qu'un sous-ensemble — mais un sous-ensemble se périme dès
 * qu'une vue ajoute une icône, et le symptôme est discret : le nom de la
 * ligature s'affiche en toutes lettres à la place du dessin.
 *
 * Depuis que la police est hébergée par nous, le fichier `.woff2` fait partie
 * de ce qui se périme. La commande le retélécharge donc elle-même et écrit à
 * côté la liste sur laquelle il a été construit ; `IconSubsetTest` compare
 * cette liste à la configuration. Sans ce manifeste, une icône ajoutée
 * passerait les tests et s'afficherait en toutes lettres en production.
 *
 * ⚠️ Une ligature construite dynamiquement — `{{ $icone }}` dans une boucle —
 * reste invisible à l'analyse. Écris le nom en clair dans le balisage, ou
 * passe-le par une propriété `icon="..."`, qui, elle, est reconnue.
 */
class SyncIcons extends Command
{
    protected $signature = 'icons:sync {--check : Échoue si la liste est périmée, sans rien réécrire}';

    protected $description = 'Régénère la liste des icônes Material Symbols et le fichier de police qui les sert';

    /** Google sert un woff2 à un navigateur, et un ttf à tout le reste. */
    private const AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36';

    private const RAPPEL = 'relancer depuis une machine ayant accès à fonts.googleapis.com, sans quoi les nouvelles icônes s\'afficheront en toutes lettres.';

    public function handle(): int
    {
        $used = self::scan();
        $configured = config('icons.names', []);

        if ($used === $configured) {
            $this->info(count($used).' icônes, liste à jour.');

            // La liste peut être à jour et le fichier de police périmé : c'est
            // même le cas le plus probable, puisque l'un se régénère à la main
            // et l'autre pas. On vérifie le manifeste avant de sortir.
            return $this->policeAJour($used) ? self::SUCCESS : $this->telechargerPolice($used);
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

        return $this->telechargerPolice($used);
    }

    /**
     * Le fichier de police sert-il exactement la liste demandée ?
     *
     * @param  array<int, string>  $icones
     */
    private function policeAJour(array $icones): bool
    {
        $manifeste = public_path('fonts/material-symbols-subset.json');

        return File::exists($manifeste)
            && File::exists(public_path('fonts/material-symbols-subset.woff2'))
            && json_decode((string) File::get($manifeste), true) === $icones;
    }

    /**
     * Récupère le sous-ensemble de police et note la liste qui l'a produit.
     *
     * Seul l'axe `FILL` est demandé : `wght` et `GRAD` ne sont employés nulle
     * part, et l'axe optique `opsz` coûtait 14,7 Ko pour une correction de
     * graisse qu'un écran de milieu de gamme ne rend pas.
     *
     * @param  array<int, string>  $icones
     */
    private function telechargerPolice(array $icones): int
    {
        $css = Http::withHeaders(['User-Agent' => self::AGENT])
            ->get('https://fonts.googleapis.com/css2', [
                'family' => 'Material Symbols Outlined:FILL@0..1',
                'icon_names' => implode(',', $icones),
                'display' => 'block',
            ]);

        if (! $css->successful() || ! preg_match('~url\((https://[^)]+)\)~', $css->body(), $m)) {
            $this->warn('Police non retéléchargée : '.self::RAPPEL);

            return self::SUCCESS;
        }

        $woff2 = Http::withHeaders(['User-Agent' => self::AGENT])->get($m[1]);

        if (! $woff2->successful()) {
            $this->warn('Police non retéléchargée : '.self::RAPPEL);

            return self::SUCCESS;
        }

        File::put(public_path('fonts/material-symbols-subset.woff2'), $woff2->body());
        File::put(public_path('fonts/material-symbols-subset.json'),
            json_encode($icones, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        $this->info(sprintf('Police régénérée : %.1f Ko.', strlen($woff2->body()) / 1024));

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

            // Les icônes passent toutes par `<x-icon name="…" />` : c'est là
            // que se trouve la ligature depuis que le composant existe. Le
            // motif précédent, qui lisait le contenu d'un `<span>` porteur de
            // la classe, ne trouve plus rien — il est conservé pour une vue
            // qui reviendrait à la forme brute, où il servirait de filet.
            preg_match_all('/<x-icon\b[^>]*?\bname="([a-z0-9_]+)"/', $contenu, $m);
            $noms = array_merge($noms, $m[1]);

            preg_match_all('/material-symbols-outlined[^>]*>\s*([a-z0-9_]+)\s*</', $contenu, $m);
            $noms = array_merge($noms, $m[1]);

            // Les icônes passées en propriété de composant — <x-empty-state
            // icon="person_search"> — n'apparaissent pas dans le balisage sous
            // forme de ligature. Sans cette passe, elles disparaissaient du
            // sous-ensemble et s'affichaient en toutes lettres.
            preg_match_all('/\bicon=(?:"|\x27)([a-z0-9_]+)(?:"|\x27)/', $contenu, $m);
            $noms = array_merge($noms, $m[1]);

            // Une ligature écrite dans un tableau PHP de la vue, puis passée
            // au composant par `:name`, reste invisible aux deux motifs
            // ci-dessus. C'est ainsi que `add_circle` s'est affiché en
            // toutes lettres dans le bouton de la colonne latérale : le
            // symptôme exact que ce mécanisme sert à éviter.
            preg_match_all('/\x27icone?\x27\s*=>\s*\x27([a-z0-9_]+)\x27/', $contenu, $m);
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
