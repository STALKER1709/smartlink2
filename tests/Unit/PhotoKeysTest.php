<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Trois fichiers nomment les mêmes catégories : categories.json (écrit par le
 * générateur d'illustrations), fetch.mjs (qui télécharge les photographies) et
 * photos:import (qui les pose sur les services). Une clé qui diverge ne casse
 * rien bruyamment : le fichier téléchargé s'appelle « climatisation-1.jpg », la
 * commande ne reconnaît pas la clé, l'ignore, et la catégorie reste illustrée
 * par un dessin sans que personne ne comprenne pourquoi.
 */
class PhotoKeysTest extends TestCase
{
    public function test_les_cles_de_fetch_couvrent_exactement_les_categories(): void
    {
        $racine = dirname(__DIR__, 2);

        $categories = json_decode(file_get_contents($racine.'/database/seeders/data/images/categories.json'), true);
        $attendues = array_values(array_unique($categories));
        sort($attendues);

        $source = file_get_contents($racine.'/design/photos/fetch.mjs');
        preg_match('/const REQUETES = \{(.*?)\n\};/s', $source, $bloc);
        $this->assertNotEmpty($bloc, 'Le bloc REQUETES est introuvable dans fetch.mjs.');

        preg_match_all('/^\s{4}([a-z]+):/m', $bloc[1], $trouvees);
        $obtenues = $trouvees[1];
        sort($obtenues);

        $this->assertSame($attendues, $obtenues);
    }
}
