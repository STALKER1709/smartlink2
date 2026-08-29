<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

/**
 * La charte, tenue par une lecture des vues plutôt que par la discipline.
 *
 * Une charte graphique ne se défait jamais d'un coup : elle se défait d'une
 * vue à la fois, chacune pour une bonne raison locale — un rayon un peu plus
 * doux ici, un blanc en dur là parce qu'on ne se rappelait plus le nom du
 * jeton. Rien ne casse, personne ne le voit, et six mois plus tard l'écran
 * n'appartient plus au même produit que son voisin.
 *
 * Chaque règle ci-dessous a été enfreinte au moins une fois dans ce dépôt.
 */
class CharteTest extends TestCase
{
    /** Les fichiers autorisés à porter une exception, et pourquoi. */
    private const EXCEPTIONS = [
        // Une bulle de conversation n'est pas une carte : sa forme est ce qui
        // la distingue du reste de la page. C'est le motif des maquettes.
        'rounded-2xl' => [
            'partials/chatbot-widget.blade.php',
            'components/message-thread.blade.php',
        ],
        // Modales, menus et panneau de l'assistant : les seuls éléments qui
        // passent au-dessus d'un contenu dont on ignore la couleur.
        'shadow' => [
            'components/modal.blade.php',
            'components/dropdown.blade.php',
            'partials/chatbot-widget.blade.php',
        ],
        // Le jaune MTN, le bleu Orange et le vert WhatsApp sont l'identité de
        // ces services, pas notre palette : les traduire en jetons les rendrait
        // méconnaissables, et c'est à ça qu'ils servent.
        'couleur-brute' => [
            'provider/subscription/checkout.blade.php',
            'providers/show.blade.php',
        ],
    ];

    /**
     * @return array<int, string> chemin relatif de chaque vue
     */
    private function vues(): array
    {
        $racine = dirname(__DIR__, 2).'/resources/views';
        $chemins = [];

        foreach (Finder::create()->files()->in($racine)->name('*.blade.php') as $fichier) {
            $chemins[] = str_replace('\\', '/', $fichier->getRelativePathname());
        }

        sort($chemins);

        return $chemins;
    }

    private function contenu(string $relatif): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2).'/resources/views/'.$relatif);
    }

    /**
     * Le contenu sans ses commentaires.
     *
     * Deux vues expliquent en commentaire pourquoi l'or de Google (#fbbc04) en
     * a été retiré. Une lecture naïve y voyait la faute que le commentaire
     * raconte avoir corrigée.
     */
    private function contenuSansCommentaires(string $relatif): string
    {
        $contenu = $this->contenu($relatif);
        $contenu = preg_replace('/\{\{--.*?--\}\}/s', '', $contenu) ?? $contenu;
        $contenu = preg_replace('~/\*.*?\*/~s', '', $contenu) ?? $contenu;

        return preg_replace('~^\s*//.*$~m', '', $contenu) ?? $contenu;
    }

    public function test_containers_keep_the_twelve_pixel_radius(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            if (in_array($vue, self::EXCEPTIONS['rounded-2xl'], true)) {
                continue;
            }

            if (str_contains($this->contenu($vue), 'rounded-2xl')) {
                $fautifs[] = $vue;
            }
        }

        $this->assertSame([], $fautifs,
            'Rayon hors charte (12 px pour un conteneur) : '.implode(', ', $fautifs));
    }

    public function test_depth_comes_from_borders_not_shadows(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            if (in_array($vue, self::EXCEPTIONS['shadow'], true)) {
                continue;
            }

            // `shadow-sm` reste toléré : il ne crée pas de profondeur, il
            // adoucit un bord. Les paliers au-delà, si.
            if (preg_match('/shadow-(md|lg|xl|2xl|inner)\b/', $this->contenu($vue))) {
                $fautifs[] = $vue;
            }
        }

        $this->assertSame([], $fautifs,
            'La profondeur vient des bordures, pas des ombres : '.implode(', ', $fautifs));
    }

    public function test_nothing_lifts_on_hover(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            // `translate-x` sur un pictogramme est toléré : la flèche d'un
            // lien avance de deux pixels *à l'intérieur* de la zone survolée,
            // sans rien déplacer autour d'elle. Ce que la charte refuse, c'est
            // qu'un bloc se soulève — agrandissement, ombre, montée.
            if (preg_match('/hover:(scale-|shadow-|-?translate-y-)/', $this->contenu($vue))) {
                $fautifs[] = $vue;
            }
        }

        // Un élément qui bouge déplace ce que l'œil suivait ; répété sur une
        // liste, il rend la page instable. L'état survolé se marque par un
        // changement de fond.
        $this->assertSame([], $fautifs,
            'Rien ne se soulève au survol : '.implode(', ', $fautifs));
    }

    public function test_colours_go_through_their_token(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            if (in_array($vue, self::EXCEPTIONS['couleur-brute'], true)) {
                continue;
            }

            $contenu = $this->contenuSansCommentaires($vue);

            // `bg-white`, `text-black`, `bg-gray-200`, `#0f6f4c` en dur : la
            // palette a un nom pour chacun, et un jeton se change en un point.
            if (preg_match('/\b(bg|text|border)-(white|black)\b/', $contenu)
                || preg_match('/\b(bg|text|border)-(gray|slate|zinc|neutral|stone|red|green|blue|yellow|amber|orange)-\d{2,3}\b/', $contenu)
                || preg_match('/#[0-9a-fA-F]{6}\b/', $contenu)) {
                $fautifs[] = $vue;
            }
        }

        $this->assertSame([], $fautifs,
            'Couleur hors palette, sans jeton : '.implode(', ', $fautifs));
    }

    public function test_headings_stay_inside_the_four_steps(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            // Seuls les vrais titres sont jugés : la famille Hanken Grotesk
            // sert aussi aux monogrammes d'avatar et au mot-logo du pied de
            // page, qui ne sont pas des titres et n'ont pas de palier à tenir.
            if (preg_match('/<h[1-4][^>]*\bclass="[^"]*\btext-(xs|sm|base|lg|xl|2xl|3xl|4xl)\b[^"]*"/', $this->contenuSansCommentaires($vue), $m)) {
                $fautifs[] = $vue.' ('.mb_substr($m[0], 0, 70).')';
            }
        }

        $this->assertSame([], $fautifs,
            'Titre écrit hors de l\'échelle : '.implode(', ', $fautifs));
    }
}
