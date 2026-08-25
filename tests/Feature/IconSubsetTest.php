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

    public function test_the_layouts_share_one_list(): void
    {
        foreach (['app', 'guest'] as $gabarit) {
            $contenu = file_get_contents(resource_path("views/layouts/{$gabarit}.blade.php"));

            $this->assertStringContainsString("@include('partials.icon-font')", $contenu,
                "Le gabarit {$gabarit} doit inclure la liste partagée plutôt que la recopier.");
        }
    }
}
