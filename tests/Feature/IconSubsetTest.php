<?php

namespace Tests\Feature;

use App\Console\Commands\SyncIcons;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Le jeu d'icônes, tenu par une lecture des vues plutôt que par la mémoire.
 *
 * Une icône ajoutée dans une vue sans être portée dans la table et dans la
 * police sous-ensemblée ne casse rien : elle ne s'affiche simplement pas.
 * C'est le pire des symptômes — un vide, à un endroit où l'on n'allait pas
 * regarder.
 */
class IconSubsetTest extends TestCase
{
    /** @return array<string, mixed> */
    private function manifeste(): array
    {
        $chemin = public_path('fonts/icones.json');
        $this->assertFileExists($chemin, 'Manifeste absent : lancer php artisan icons:sync.');

        return json_decode((string) File::get($chemin), true);
    }

    public function test_every_icon_used_in_a_view_has_a_translation(): void
    {
        $employees = SyncIcons::scan();
        $table = config('icons.correspondance');

        $this->assertSame([], array_values(array_diff($employees, array_keys($table))),
            'Icône employée sans correspondance Font Awesome : lancer php artisan icons:sync.');
    }

    public function test_the_stylesheet_draws_every_icon_used(): void
    {
        $feuille = (string) File::get(resource_path('css/icones.css'));
        $table = config('icons.correspondance');

        $manquantes = [];

        foreach (SyncIcons::scan() as $nom) {
            if (! str_contains($feuille, '.icone-'.$table[$nom].' {')) {
                $manquantes[] = $nom;
            }
        }

        $this->assertSame([], $manquantes,
            'Icône sans glyphe dans la feuille — elle ne s\'affichera pas du tout : '.implode(', ', $manquantes));
    }

    /**
     * L'empreinte, et pourquoi elle est là.
     *
     * Le manifeste seul est une note posée à côté des fichiers, et deux
     * sessions travaillant en parallèle l'ont déjà séparée d'eux. L'une avait
     * régénéré la police depuis sa propre liste, plus courte ; au rebase, le
     * `.woff2` — binaire, donc sans conflit — est passé de son côté et le
     * manifeste du mien. Les deux tests de l'époque comparaient deux fichiers
     * texte issus du même côté : ils sont restés verts, et sept icônes se sont
     * imprimées en toutes lettres en production.
     */
    public function test_the_fonts_are_the_ones_the_manifest_describes(): void
    {
        $manifeste = $this->manifeste();

        foreach (['plein', 'contour'] as $famille) {
            $fichier = public_path("fonts/icones-{$famille}.woff2");

            $this->assertFileExists($fichier);
            $this->assertSame($manifeste['sha256'][$famille] ?? null, hash_file('sha256', $fichier),
                "La police « {$famille} » n'est pas celle que décrit le manifeste : lancer php artisan icons:sync.");
        }

        $this->assertSame(SyncIcons::scan(), $manifeste['icones'] ?? null,
            'Le manifeste ne décrit pas le jeu employé : lancer php artisan icons:sync.');
    }

    /**
     * Le contour n'existe que pour les icônes dont il porte un sens.
     *
     * Font Awesome Free ne dessine que 27 des 97 icônes d'ici en contour :
     * l'ouvrir à d'autres donnerait un jeu dont une partie est plus légère que
     * le reste, et le glyphe manquerait purement et simplement pour toutes
     * celles qui n'ont pas de variante.
     */
    public function test_only_binary_icons_ask_for_an_outline(): void
    {
        $this->assertSame(SyncIcons::BINAIRES, config('icons.binaires'));

        $fautifs = [];

        foreach (File::allFiles(resource_path('views')) as $fichier) {
            preg_match_all('/<x-icon\b[^>]*>/s', $fichier->getContents(), $balises);

            foreach ($balises[0] as $balise) {
                if (! preg_match('/\bfilled\b(?!=)/', $balise)) {
                    continue;
                }

                // Une expression `:name` peut viser une binaire selon l'état :
                // on ne peut pas trancher à la lecture, et le composant, lui,
                // le fait à l'exécution.
                if (str_contains($balise, ':name=')) {
                    continue;
                }

                preg_match('/\bname="([a-z0-9_]+)"/', $balise, $nom);

                if (! in_array($nom[1] ?? '', SyncIcons::BINAIRES, true)) {
                    $fautifs[] = $fichier->getRelativePathname().' ('.($nom[1] ?? '?').')';
                }
            }
        }

        $this->assertSame([], $fautifs,
            'Remplissage demandé sur une icône dont il ne dit aucun état : '.implode(', ', $fautifs));
    }

    /**
     * Une icône écrite en première position d'un tableau échappe au relevé —
     * et à ce test, qui lit le dépôt avec le même relevé. Le symptôme est un
     * pictogramme absent, sans la moindre erreur.
     */
    public function test_no_view_hides_an_icon_in_a_positional_tuple(): void
    {
        $fautifs = [];

        foreach (File::allFiles(resource_path('views')) as $fichier) {
            $contenu = $fichier->getContents();

            if (! str_contains($contenu, '<x-icon') && ! str_contains($contenu, ':name=')) {
                continue;
            }

            preg_match_all('/\[\s*\x27([a-z][a-z0-9_]{2,})\x27\s*,/', $contenu, $m);

            foreach ($m[1] as $premier) {
                if (array_key_exists($premier, config('icons.correspondance'))) {
                    $fautifs[] = $fichier->getRelativePathname().' ('.$premier.')';
                }
            }
        }

        $this->assertSame([], $fautifs,
            'Icône en première position d\'un tableau : la nommer par une clé — '.implode(', ', $fautifs));
    }
}
