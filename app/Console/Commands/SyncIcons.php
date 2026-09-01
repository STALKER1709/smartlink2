<?php

namespace App\Console\Commands;

use App\Support\NavigationLinks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

/**
 * Régénère le jeu d'icônes : la table, les deux polices, la feuille de style.
 *
 * L'application nomme ses icônes dans son propre vocabulaire — `handyman`,
 * `location_on`, `check_circle` — hérité de Material Symbols et resté parce
 * qu'il est lisible. `config/icons.php` traduit ce vocabulaire en noms Font
 * Awesome ; les 152 appels des vues n'ont donc pas à connaître la fonte
 * employée, et en changer à nouveau ne toucherait que cette table.
 *
 * Trois décisions valent d'être dites, parce qu'elles ne se devinent pas en
 * lisant le code généré :
 *
 * 1. **Une seule famille : le plein.** Font Awesome Free ne dessine que 27 des
 *    97 icônes d'ici en contour. Mélanger les deux donnerait un jeu où un
 *    quart des pictogrammes est plus léger que les autres — le défaut le plus
 *    visible qu'un jeu d'icônes puisse avoir.
 *
 * 2. **Le contour reste, pour les seules icônes dont il porte un sens.**
 *    L'étoile d'une note et le cœur d'un favori disent leur état par leur
 *    remplissage : vide, on ne l'a pas fait ; plein, on l'a fait. Ailleurs,
 *    `filled` ne voulait rien dire et a été retiré des vues plutôt qu'ignoré
 *    en silence.
 *
 * 3. **La police est sous-ensemblée ici, pas servie entière.** Le plein de
 *    Font Awesome pèse 155 Ko pour 1 976 glyphes ; on en emploie 97. Le
 *    sous-ensemble est produit par `pyftsubset` (fontTools).
 *
 * Le glyphe est posé par `content` sur un pseudo-élément, et non par une
 * ligature. C'est ce qui règle au passage un défaut d'accessibilité de la
 * forme précédente : une ligature est du texte, et un lecteur d'écran
 * annonçait « plumbing », « arrow_forward », « star ».
 */
class SyncIcons extends Command
{
    protected $signature = 'icons:sync {--check : Échoue si le jeu est périmé, sans rien réécrire}';

    protected $description = 'Régénère la table, les polices sous-ensemblées et la feuille des icônes';

    private const VERSION_FA = '6.7.2';

    private const CDN = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/'.self::VERSION_FA;

    /** Les seules icônes dont le contour porte un sens : un état binaire. */
    public const BINAIRES = ['star', 'favorite'];

    private const RAPPEL = 'relancer depuis une machine ayant accès à cdnjs.cloudflare.com et disposant de fontTools (pip install fonttools brotli).';

    public function handle(): int
    {
        $employees = self::scan();
        $table = config('icons.correspondance', []);

        $sansTraduction = array_diff($employees, array_keys($table));

        if ($sansTraduction !== []) {
            $this->error('Icônes employées sans correspondance Font Awesome : '.implode(', ', $sansTraduction));
            $this->line('Ajoute-les à la table de config/icons.php, puis relance.');

            return self::FAILURE;
        }

        $superflues = array_diff(array_keys($table), $employees);

        if ($superflues !== []) {
            $this->line('Traduites sans être employées : '.implode(', ', $superflues));
        }

        if ($this->aJour($employees)) {
            $this->info(count($employees).' icônes, jeu à jour.');

            return self::SUCCESS;
        }

        if ($this->option('check')) {
            $this->error('Jeu périmé. Lancer php artisan icons:sync.');

            return self::FAILURE;
        }

        return $this->regenerer($employees, $table);
    }

    /**
     * Les polices servent-elles exactement le jeu employé ?
     *
     * Le manifeste ne suffit pas : c'est une note posée à côté des fichiers, et
     * deux sessions travaillant en parallèle l'ont déjà séparée d'eux. L'une
     * avait régénéré la police depuis sa propre liste, plus courte ; au rebase,
     * le `.woff2` — binaire, donc sans conflit — est passé de son côté et le
     * manifeste du mien. Les tests de l'époque comparaient deux fichiers texte
     * issus du même côté : ils sont restés verts, et « more_vert » s'est
     * imprimé en toutes lettres dans le menu de chaque service.
     *
     * Le manifeste porte donc aussi l'empreinte des fichiers qu'il décrit.
     *
     * @param  array<int, string>  $employees
     */
    private function aJour(array $employees): bool
    {
        $manifeste = public_path('fonts/icones.json');

        if (! File::exists($manifeste)) {
            return false;
        }

        $decrit = json_decode((string) File::get($manifeste), true);

        if (! is_array($decrit) || ($decrit['icones'] ?? null) !== $employees) {
            return false;
        }

        foreach (['plein', 'contour'] as $famille) {
            $fichier = public_path("fonts/icones-{$famille}.woff2");

            if (! File::exists($fichier) || ($decrit['sha256'][$famille] ?? null) !== hash_file('sha256', $fichier)) {
                return false;
            }
        }

        return File::exists(resource_path('css/icones.css'));
    }

    /**
     * @param  array<int, string>  $employees
     * @param  array<string, string>  $table
     */
    private function regenerer(array $employees, array $table): int
    {
        $codes = $this->codesFontAwesome();

        if ($codes === null) {
            $this->warn('Jeu non régénéré : '.self::RAPPEL);

            return self::FAILURE;
        }

        // Le contour n'est demandé que pour les icônes dont il dit l'état.
        $plein = [];
        $contour = [];

        foreach ($employees as $nom) {
            $fa = $table[$nom];

            if (! isset($codes[$fa])) {
                $this->error("« {$fa} » n'existe pas dans Font Awesome ".self::VERSION_FA.' (pour '.$nom.').');

                return self::FAILURE;
            }

            $plein[$fa] = $codes[$fa];

            if (in_array($nom, self::BINAIRES, true)) {
                $contour[$fa] = $codes[$fa];
            }
        }

        $empreintes = [];

        foreach (['plein' => ['fa-solid-900', $plein], 'contour' => ['fa-regular-400', $contour]] as $famille => [$source, $glyphes]) {
            $octets = $this->sousEnsembler($source, array_values($glyphes));

            if ($octets === null) {
                $this->warn('Jeu non régénéré : '.self::RAPPEL);

                return self::FAILURE;
            }

            $cible = public_path("fonts/icones-{$famille}.woff2");
            File::put($cible, $octets);
            $empreintes[$famille] = hash('sha256', $octets);

            $this->info(sprintf('%-8s %d glyphes, %.1f Ko.', $famille, count($glyphes), strlen($octets) / 1024));
        }

        File::put(public_path('fonts/icones.json'), json_encode([
            'font_awesome' => self::VERSION_FA,
            'icones' => $employees,
            'sha256' => $empreintes,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        File::put(resource_path('css/icones.css'), $this->feuille($employees, $table, $codes));
        File::put(config_path('icons.php'), $this->tableGeneree($table));

        $this->info('Feuille et table réécrites. Relancer npm run build.');

        return self::SUCCESS;
    }

    /**
     * Le nom de chaque icône Font Awesome et son point de code.
     *
     * Lus dans la feuille officielle plutôt que dans un fichier de métadonnées :
     * c'est la seule ressource que le CDN sert pour la distribution Free, et
     * elle porte aussi les alias — `check-circle` y renvoie au même code que
     * `circle-check`, ce qui évite de se tromper de génération de nom.
     *
     * @return array<string, string>|null
     */
    private function codesFontAwesome(): ?array
    {
        $css = Http::timeout(60)->get(self::CDN.'/css/all.min.css');

        if (! $css->successful()) {
            return null;
        }

        $codes = [];
        preg_match_all('/((?:\.fa-[a-z0-9-]+\s*,\s*)*\.fa-[a-z0-9-]+)\s*\{\s*--fa:\s*"\\\\([0-9a-fA-F]+)"/', $css->body(), $blocs, PREG_SET_ORDER);

        foreach ($blocs as $bloc) {
            preg_match_all('/\.fa-([a-z0-9-]+)/', $bloc[1], $noms);

            foreach ($noms[1] as $nom) {
                $codes[$nom] = strtolower($bloc[2]);
            }
        }

        return $codes !== [] ? $codes : null;
    }

    /**
     * @param  array<int, string>  $codes  points de code en hexadécimal
     */
    private function sousEnsembler(string $source, array $codes): ?string
    {
        $police = Http::timeout(90)->get(self::CDN."/webfonts/{$source}.woff2");

        if (! $police->successful()) {
            return null;
        }

        $entree = tempnam(sys_get_temp_dir(), 'fa').'.woff2';
        $sortie = tempnam(sys_get_temp_dir(), 'fa').'.subset.woff2';
        File::put($entree, $police->body());

        $resultat = Process::timeout(120)->run([
            'pyftsubset', $entree,
            '--unicodes='.implode(',', array_map(fn (string $c) => 'U+'.$c, $codes)),
            '--flavor=woff2',
            '--layout-features=',
            '--no-hinting',
            '--desubroutinize',
            '--output-file='.$sortie,
        ]);

        $octets = $resultat->successful() && File::exists($sortie) ? File::get($sortie) : null;

        File::delete([$entree, $sortie]);

        if ($octets === null) {
            $this->error('pyftsubset a échoué : '.trim($resultat->errorOutput() ?: $resultat->output()));
        }

        return $octets;
    }

    /**
     * @param  array<int, string>  $employees
     * @param  array<string, string>  $table
     * @param  array<string, string>  $codes
     */
    private function feuille(array $employees, array $table, array $codes): string
    {
        $glyphes = [];

        foreach ($employees as $nom) {
            $glyphes[] = sprintf('.icone-%s { --glyphe: "\\%s"; }', $table[$nom], $codes[$table[$nom]]);
        }

        $glyphes = implode("\n", array_unique($glyphes));
        $binaires = implode(', ', self::BINAIRES);

        return <<<CSS
            /*
             * Fichier généré : ne pas modifier à la main.
             * Régénérer avec `php artisan icons:sync`, puis `npm run build`.
             *
             * Les glyphes viennent de Font Awesome Free {$this->version()}, sous-ensemblés
             * aux seules icônes employées. Le plein est la famille unique ; le
             * contour n'existe que pour {$binaires}, dont le remplissage dit l'état.
             */

            @font-face {
                font-family: 'SmartLink Icons';
                font-style: normal;
                font-weight: 900;
                font-display: block;
                src: url('/fonts/icones-plein.woff2') format('woff2');
            }

            @font-face {
                font-family: 'SmartLink Icons';
                font-style: normal;
                font-weight: 400;
                font-display: block;
                src: url('/fonts/icones-contour.woff2') format('woff2');
            }

            .icone {
                font-family: 'SmartLink Icons';
                font-weight: 900;
                font-style: normal;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                /* Le glyphe s'aligne sur la ligne de base du texte qu'il
                   accompagne au lieu de flotter au-dessus. */
                line-height: 1;
                flex-shrink: 0;
                /* Un pictogramme ne se sélectionne pas : sans cela, un glisser
                   sur une carte le surligne au milieu du texte. */
                user-select: none;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }

            .icone::before { content: var(--glyphe, ""); }

            /* Le contour, pour les seules icônes dont il porte un sens. */
            .icone-contour { font-weight: 400; }

            /* Une taille d'icône est une dimension, pas un palier
               typographique : elle est donnée en pixels et se lit à
               l'identique quelle que soit la taille du texte autour. */
            .icone-taille-xs { font-size: 16px; width: 16px; height: 16px; }
            .icone-taille-sm { font-size: 18px; width: 18px; height: 18px; }
            .icone-taille-md { font-size: 20px; width: 20px; height: 20px; }
            .icone-taille-lg { font-size: 24px; width: 24px; height: 24px; }
            .icone-taille-xl { font-size: 32px; width: 32px; height: 32px; }
            .icone-taille-2xl { font-size: 40px; width: 40px; height: 40px; }
            .icone-taille-3xl { font-size: 48px; width: 48px; height: 48px; }

            {$glyphes}

            CSS;
    }

    private function version(): string
    {
        return self::VERSION_FA;
    }

    /**
     * @param  array<string, string>  $table
     */
    private function tableGeneree(array $table): string
    {
        ksort($table);

        $lignes = implode("\n", array_map(
            fn (string $nom, string $fa) => sprintf('        %-26s => %s,', "'{$nom}'", "'{$fa}'"),
            array_keys($table),
            $table,
        ));

        $binaires = implode("\n", array_map(fn (string $n) => "        '{$n}',", self::BINAIRES));

        return <<<PHP
            <?php

            /*
             * Le vocabulaire d'icônes de l'application, traduit en noms Font Awesome.
             *
             * Les vues nomment leurs icônes ici — `handyman`, `location_on` — et ignorent
             * la fonte employée. En changer ne toucherait que cette table.
             *
             * Fichier généré : ne pas modifier à la main.
             * Régénérer avec `php artisan icons:sync` après avoir ajouté une icône dans
             * une vue, sinon elle ne s'affichera pas du tout.
             */

            return [

                'correspondance' => [
            {$lignes}
                ],

                /*
                 * Les seules icônes dont le contour porte un sens : leur remplissage dit
                 * un état binaire — l'étoile d'une note, le cœur d'un favori.
                 */
                'binaires' => [
            {$binaires}
                ],

            ];

            PHP;
    }

    /**
     * Les icônes réellement nommées dans les vues.
     *
     * @return array<int, string>
     */
    public static function scan(): array
    {
        // La barre d'onglets rend ses icônes par une expression : aucun de ses
        // noms n'apparaît littéralement dans le balisage. La table les énumère
        // elle-même, ce qui vaut mieux qu'une expression régulière taillée sur
        // sa forme du jour.
        $noms = NavigationLinks::icones();

        foreach (File::allFiles(resource_path('views')) as $fichier) {
            $contenu = $fichier->getContents();

            preg_match_all('/<x-icon\b[^>]*?\bname="([a-z0-9_]+)"/', $contenu, $m);
            $noms = array_merge($noms, $m[1]);

            // Les icônes passées en propriété de composant — <x-empty-state
            // icon="person_search"> — n'apparaissent pas sous forme de nom
            // dans le balisage. Sans cette passe, elles disparaissaient du
            // sous-ensemble.
            preg_match_all('/\bicon=(?:"|\x27)([a-z0-9_]+)(?:"|\x27)/', $contenu, $m);
            $noms = array_merge($noms, $m[1]);

            // Un nom écrit dans un tableau PHP de la vue, puis passé au
            // composant par `:name`, reste invisible aux deux motifs ci-dessus.
            // C'est ainsi que `add_circle` a disparu du bouton de la colonne
            // latérale.
            //
            // D'où la convention, qui n'est pas cosmétique : dans une vue, une
            // icône se nomme par une clé, elle ne se pose pas en première
            // position d'un tableau. `['handshake', $titre, $texte]` a échappé
            // à ce motif, et l'icône a manqué sur l'accueil — pendant que le
            // test restait vert, puisqu'une icône que ce relevé ne voit pas ne
            // peut pas lui manquer.
            preg_match_all('/\x27icone?\x27\s*=>\s*\x27([a-z0-9_]+)\x27/', $contenu, $m);
            $noms = array_merge($noms, $m[1]);

            // Un nom écrit en clair dans l'expression passée à `:name` — le
            // plus souvent une alternative, `$actif ? 'check_circle' :
            // 'pause_circle'`. Les deux branches sont des littéraux, mais
            // aucune n'est un attribut.
            preg_match_all('/:name="([^"]*)"/', $contenu, $expressions);

            foreach ($expressions[1] as $expression) {
                // La négation regarde le caractère d'avant : dans
                // `$tuile['icone']`, « icone » est une clé de tableau, pas un
                // nom d'icône. Sans elle, le relevé réclamait une icône
                // nommée « icone », que rien ne peut traduire.
                preg_match_all('/(?<!\[)\x27([a-z][a-z0-9_]*)\x27/', $expression, $litteraux);
                $noms = array_merge($noms, $litteraux[1]);
            }

            // Le composant de catégorie tient sa propre table de correspondance :
            // ces noms n'apparaissent nulle part ailleurs dans le balisage.
            if ($fichier->getFilename() === 'category-icon.blade.php') {
                preg_match_all("/=>\s*'([a-z0-9_]+)'/", $contenu, $m);
                $noms = array_merge($noms, $m[1], ['handyman']);
            }
        }

        $noms = array_values(array_unique(array_filter($noms)));
        sort($noms);

        return $noms;
    }
}
