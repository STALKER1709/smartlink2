<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Une clé de traduction française sans valeur anglaise ne casse rien : Laravel
 * rend la clé telle quelle, c'est-à-dire du français. La page s'affiche, aucun
 * journal ne bronche, et l'anglais se dégrade phrase par phrase à mesure que
 * l'interface évolue — exactement ce qui était arrivé avant que lang/en.json
 * n'existe.
 *
 * Ce test refuse la prochaine occasion de recommencer.
 */
class TranslationCoverageTest extends TestCase
{
    /**
     * Les clés dont le texte source est déjà anglais — celles de Laravel et de
     * son échafaudage d'authentification — se traduisent d'elles-mêmes.
     */
    private function estFrancaise(string $cle): bool
    {
        // La plage « À-Ý » contient aussi « × » (U+00D7), qui n'a rien de
        // français : les lettres sont listées une à une.
        return (bool) preg_match('/[àâäçéèêëîïôöùûüœÀÂÄÇÉÈÊËÎÏÔÖÙÛÜŒ]/u', $cle)
            || (bool) preg_match('/\b(le|la|les|des|une|vous|votre|pour|dans|avec|sur|est|sont)\b/iu', $cle);
    }

    /**
     * @return list<string>
     */
    private function clesDuCode(): array
    {
        $cles = [];

        foreach ([resource_path('views'), app_path()] as $racine) {
            foreach (File::allFiles($racine) as $fichier) {
                $cles = array_merge($cles, $this->clesDuFichier($fichier->getPathname()));
            }
        }

        return array_values(array_unique($cles));
    }

    /**
     * @return list<string>
     */
    private function clesDuFichier(string $chemin): array
    {
        /*
         * Deux motifs, un par sorte de guillemet. Un seul motif à
         * référence arrière s'arrête au premier guillemet de l'autre sorte
         * rencontré dans le texte — et une chaîne française en contient
         * souvent une, ne serait-ce qu'une apostrophe.
         */
        $contenu = (string) file_get_contents($chemin);
        $cles = [];

        preg_match_all('/(?:__|trans_choice)\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'/su', $contenu, $simples);
        preg_match_all('/(?:__|trans_choice)\(\s*"((?:[^"\\\\]|\\\\.)*)"/su', $contenu, $doubles);

        foreach ($simples[1] as $cle) {
            $cles[] = str_replace("\\'", "'", $cle);
        }

        foreach ($doubles[1] as $cle) {
            $cles[] = str_replace('\\"', '"', $cle);
        }

        return $cles;
    }

    public function test_every_french_key_has_an_english_translation(): void
    {
        $anglais = json_decode((string) file_get_contents(lang_path('en.json')), true);

        $orphelines = [];

        foreach ($this->clesDuCode() as $cle) {
            // Une clé de fichier (« ui.nav.services ») se résout ailleurs.
            if (str_contains(explode(' ', $cle)[0], '.')) {
                continue;
            }

            if ($this->estFrancaise($cle) && ! isset($anglais[$cle])) {
                $orphelines[] = $cle;
            }
        }

        $this->assertSame([], $orphelines, count($orphelines).' clé(s) française(s) sans traduction anglaise.');
    }

    /**
     * L'inverse : une traduction dont la clé a disparu du code alourdit le
     * fichier sans que personne ne s'en aperçoive.
     */
    public function test_no_translation_is_left_behind(): void
    {
        $anglais = json_decode((string) file_get_contents(lang_path('en.json')), true);
        $utilisees = $this->clesDuCode();

        $inutiles = array_values(array_filter(
            array_keys($anglais),
            fn (string $cle) => $this->estFrancaise($cle) && ! in_array($cle, $utilisees, true),
        ));

        $this->assertSame([], $inutiles, count($inutiles).' traduction(s) sans clé correspondante dans le code.');
    }
}
