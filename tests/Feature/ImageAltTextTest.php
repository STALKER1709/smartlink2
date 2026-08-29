<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Une image sans texte alternatif est muette pour qui navigue au lecteur
 * d'écran, et invisible pour un moteur. C'est une omission qui ne se voit
 * jamais à l'œil : la page s'affiche parfaitement.
 *
 * Ce test lit les vues plutôt que les pages rendues — une balise oubliée dans
 * une branche `@if` rarement empruntée compte autant que les autres.
 */
class ImageAltTextTest extends TestCase
{
    #[Test]
    public function every_image_in_the_views_carries_an_alt_attribute(): void
    {
        $sansAlt = [];

        foreach ($this->balisesImage() as $vue => $balises) {
            foreach ($balises as $balise) {
                if (! preg_match('/\balt\s*=/', $balise)) {
                    $sansAlt[] = $vue.' : '.Str::limit($balise, 80);
                }
            }
        }

        $this->assertSame(
            [],
            $sansAlt,
            "Ces images n'ont pas de texte alternatif. Décrivez-les, ou posez alt=\"\" ".
            "si l'image est décorative et qu'un libellé voisin la nomme déjà :\n".
            implode("\n", $sansAlt)
        );
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function balisesImage(): array
    {
        $trouvees = [];

        $finder = (new Finder)
            ->files()
            ->in(resource_path('views'))
            ->name('*.blade.php');

        /** @var SplFileInfo $fichier */
        foreach ($finder as $fichier) {
            /*
             * Les expressions Blade sont neutralisées d'abord : la flèche de
             * `{{ $service->title }}` referme la balise pour une expression
             * régulière naïve, qui déclare alors manquant un `alt` posé plus
             * loin. Le détecteur passerait au vert sur des vues correctes et
             * au rouge sur d'autres, au hasard de l'ordre des attributs.
             */
            $plat = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}/s', 'X', $fichier->getContents());

            if (preg_match_all('/<img\b[^>]*>/s', (string) $plat, $correspondances)) {
                $trouvees[$fichier->getRelativePathname()] = $correspondances[0];
            }
        }

        return $trouvees;
    }

    /**
     * Le détecteur lui-même doit être digne de confiance : un test qui ne
     * trouve jamais rien passe au vert quoi qu'il arrive.
     */
    #[Test]
    public function the_detector_actually_finds_the_images(): void
    {
        $total = collect($this->balisesImage())->flatten()->count();

        $this->assertGreaterThan(15, $total, 'Le détecteur ne voit plus les images des vues.');
    }
}
