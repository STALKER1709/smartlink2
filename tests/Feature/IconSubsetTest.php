<?php

namespace Tests\Feature;

use App\Console\Commands\SyncIcons;
use Tests\TestCase;

/**
 * La police d'icônes n'est pas servie en entier — 1,1 Mo pour une cinquantaine
 * d'icônes — mais un sous-ensemble se périme dès qu'une vue en ajoute une, et
 * le symptôme est discret : le nom de la ligature s'affiche en toutes lettres
 * à la place du dessin. Aucune erreur, aucun log.
 */
class IconSubsetTest extends TestCase
{
    public function test_every_icon_used_by_a_view_is_requested(): void
    {
        $manquantes = array_diff(SyncIcons::scan(), config('icons.names', []));

        $this->assertSame([], array_values($manquantes),
            'Icônes utilisées mais non demandées : elles s\'afficheront en toutes lettres. Lancer php artisan icons:sync.');
    }

    public function test_the_subset_carries_no_unused_icon(): void
    {
        $superflues = array_diff(config('icons.names', []), SyncIcons::scan());

        $this->assertSame([], array_values($superflues),
            'Icônes demandées sans être utilisées : poids inutile. Lancer php artisan icons:sync.');
    }

    /**
     * Depuis que la police est hébergée par nous, c'est le fichier `.woff2`
     * qui se périme — et lui, aucune configuration ne le trahit. La commande
     * écrit donc à côté la liste sur laquelle il a été construit.
     *
     * Sans ce contrôle, une icône ajoutée passerait les deux tests ci-dessus,
     * la liste de configuration étant à jour, et s'afficherait en toutes
     * lettres en production.
     */
    public function test_the_font_file_matches_the_configured_list(): void
    {
        $manifeste = public_path('fonts/material-symbols-subset.json');

        $this->assertFileExists($manifeste,
            'Le manifeste du sous-ensemble manque. Lancer php artisan icons:sync.');

        $servies = json_decode((string) file_get_contents($manifeste), true);

        $this->assertSame(config('icons.names', []), $servies,
            'Le fichier de police ne sert pas la liste configurée : les icônes ajoutées '
            .'depuis s\'afficheront en toutes lettres. Lancer php artisan icons:sync '
            .'depuis une machine ayant accès à fonts.googleapis.com.');
    }

    public function test_no_layout_calls_google_fonts(): void
    {
        foreach (['app', 'guest'] as $gabarit) {
            $contenu = file_get_contents(resource_path("views/layouts/{$gabarit}.blade.php"));

            // Une requête tierce bloquante sur chaque page, et l'adresse IP de
            // chaque visiteur envoyée à Google.
            $this->assertStringNotContainsString('fonts.googleapis.com', $contenu,
                "Le gabarit {$gabarit} sert ses polices lui-même : voir resources/css/app.css.");
        }
    }
}
