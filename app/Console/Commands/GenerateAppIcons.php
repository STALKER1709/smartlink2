<?php

namespace App\Console\Commands;

use GdImage;
use Illuminate\Console\Command;

/**
 * Produit les icônes d'installation à partir de la marque.
 *
 * Le logo vit dans `resources/views/components/application-logo.blade.php`,
 * en SVG : trois tracés sur une grille de 32. Les icônes du manifeste doivent
 * être des PNG matriciels — aucun système d'exploitation n'accepte le SVG pour
 * une icône d'écran d'accueil. Cette commande redessine la même géométrie plutôt
 * que de convertir le fichier, ce qui éviterait d'ajouter un moteur de rendu SVG
 * aux dépendances pour trois cercles et un trait.
 *
 * Les fichiers produits sont versionnés : ils font partie de ce qui est déployé,
 * comme `public/build`. Relancer la commande n'a de sens que si la marque change.
 */
class GenerateAppIcons extends Command
{
    protected $signature = 'icons:app';

    protected $description = "Redessine les icônes d'installation (manifeste PWA et écran d'accueil iOS)";

    /**
     * Le vert de marque, `primary` du système de design.
     */
    private const FOND = [0x00, 0x55, 0x38];

    /**
     * La part du carré occupée par le logo.
     *
     * Une icône « maskable » peut être rognée en cercle ou en goutte selon le
     * lanceur : seuls les 80 % centraux sont garantis visibles. À 58 %, le
     * tracé reste entier quelle que soit la découpe.
     */
    private const PART_DU_LOGO = 0.58;

    /**
     * Le dessin est fait quatre fois trop grand puis réduit : GD ne lisse ni
     * les arcs ni les traits épais, et un cercle tracé à la taille finale
     * ressort crénelé.
     */
    private const SURECHANTILLONNAGE = 4;

    public function handle(): int
    {
        $cibles = [
            'images/icone-192.png' => 192,
            'images/icone-512.png' => 512,
            'images/icone-apple-180.png' => 180,
        ];

        foreach ($cibles as $chemin => $taille) {
            $image = $this->dessiner($taille * self::SURECHANTILLONNAGE);
            $reduite = imagecreatetruecolor($taille, $taille);
            imagecopyresampled(
                $reduite, $image,
                0, 0, 0, 0,
                $taille, $taille,
                imagesx($image), imagesy($image),
            );

            imagepng($reduite, public_path($chemin), 9);
            imagedestroy($image);
            imagedestroy($reduite);

            $this->line("  {$chemin} — {$taille}×{$taille}");
        }

        $this->info('Icônes régénérées.');

        return self::SUCCESS;
    }

    private function dessiner(int $taille): GdImage
    {
        $image = imagecreatetruecolor($taille, $taille);
        $fond = imagecolorallocate($image, ...self::FOND);
        $trace = imagecolorallocate($image, 255, 255, 255);

        imagefilledrectangle($image, 0, 0, $taille, $taille, $fond);

        // Passage de la grille de 32 du SVG aux pixels de l'icône.
        $echelle = $taille * self::PART_DU_LOGO / 32;
        $marge = ($taille - $taille * self::PART_DU_LOGO) / 2;
        $u = fn (float $v): float => $marge + $v * $echelle;

        // Les deux nœuds : un disque plein, évidé au centre pour ne laisser
        // que l'anneau de 3 unités d'épaisseur. Ils passent avant le trait : en
        // SVG, l'intérieur d'un cercle seulement contouré n'est pas peint, et
        // l'évidement effacerait le bout du trait qui y entre.
        foreach ([[9, 23], [23, 9]] as [$cx, $cy]) {
            $this->disque($image, $u($cx), $u($cy), 6.75 * $echelle, $trace);
            $this->disque($image, $u($cx), $u($cy), 3.75 * $echelle, $fond);
        }

        // Le trait qui relie les deux nœuds, extrémités arrondies : une suite
        // de disques le long du segment, ce que GD ne sait pas faire seul.
        $rayonDuTrait = 1.5 * $echelle;
        $pas = 1 / max(1, (int) ($echelle * 8));

        for ($t = 0.0; $t <= 1.0; $t += $pas) {
            $this->disque(
                $image,
                $u(12.5 + $t * 7.0),
                $u(19.5 - $t * 7.0),
                $rayonDuTrait,
                $trace,
            );
        }

        return $image;
    }

    private function disque(GdImage $image, float $x, float $y, float $rayon, int $couleur): void
    {
        imagefilledellipse($image, (int) round($x), (int) round($y), (int) round($rayon * 2), (int) round($rayon * 2), $couleur);
    }
}
