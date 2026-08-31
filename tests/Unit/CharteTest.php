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
        // Les trois paliers d'ombre de la charte. Toute autre écriture —
        // `shadow-md`, `shadow-[0_8px_24px_rgba(0,0,0,.12)]` — est une
        // profondeur que personne n'a décidée.
        'ombre' => [
            'shadow-elevation-1',
            'shadow-elevation-2',
            'shadow-overlay',
            'shadow-none',
        ],
        // Le jaune MTN, le bleu Orange et le vert WhatsApp sont l'identité de
        // ces services, pas notre palette : les traduire en jetons les rendrait
        // méconnaissables, et c'est à ça qu'ils servent.
        'couleur-brute' => [
            'provider/subscription/checkout.blade.php',
            'providers/show.blade.php',
            // La page hors-ligne est servie par le service worker sans réseau :
            // aucune feuille de style ne l'accompagne, ses couleurs sont donc
            // écrites en clair. Ce sont celles de la palette.
            'hors-ligne.blade.php',
            // `theme-color` est une balise meta : elle prend une couleur, pas
            // une classe.
            'partials/pwa-head.blade.php',
        ],
        // Une icône n'est pas du texte : sa taille est une dimension, et
        // Material Symbols la prend en `font-size`. Les trois porteurs
        // d'icône du dépôt sont donc hors de l'échelle typographique.
        'icone' => [
            'material-symbols',
            'x-category-icon',
            'x-service-thumb',
            // La taille par défaut du pictogramme de repli, déclarée en
            // propriété du composant qui le porte.
            "'size' =>",
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

    /**
     * Les rayons intermédiaires, qui n'appartiennent à aucun rôle.
     *
     * La charte n'en connaît que trois : 12 px pour ce qui encadre, la pilule
     * pour ce qui s'actionne, 8 px pour une vignette. `rounded`, `rounded-sm`
     * et `rounded-md` ne disent rien de ce qu'ils arrondissent — ils étaient
     * quarante-trois, répartis sur vingt-quatre vues, et faisaient cinq
     * arrondis différents pour des éléments de même rôle.
     *
     * Une case à cocher garde `rounded` : elle fait 16 px de côté, et les
     * 12 px des conteneurs la rendraient ronde, donc lisible comme un bouton
     * radio — un choix exclusif au lieu d'un choix multiple.
     */
    public function test_radii_say_what_they_round(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            $lignes = explode("\n", $this->contenuSansCommentaires($vue));

            foreach ($lignes as $numero => $ligne) {
                if (! preg_match('/\brounded(-sm|-md)?(?![-a-zA-Z0-9])/', $ligne, $m)) {
                    continue;
                }

                // La balise d'une case à cocher s'ouvre souvent quelques
                // lignes plus haut : on regarde la fenêtre, pas la ligne.
                $fenetre = implode(' ', array_slice($lignes, max(0, $numero - 4), 5));

                if (str_contains($fenetre, 'type="checkbox"')) {
                    continue;
                }

                $fautifs[] = $vue.':'.($numero + 1).' ('.$m[0].')';
            }
        }

        $this->assertSame([], $fautifs,
            'Rayon sans rôle — 12 px encadre, la pilule s\'actionne, 8 px illustre : '.implode(', ', $fautifs));
    }

    /**
     * Un bouton d'action passe par son composant.
     *
     * Cinquante-trois boutons primaires étaient écrits à la main, en
     * quarante-trois formulations : px-4, px-5, px-6, px-8 ; py-2, py-2.5,
     * py-3, py-[14px] ; `text-label-md` ici, `text-button-text` là. La cause
     * tenait à ce que le composant ne rendait qu'un `<button>` alors que
     * dix-sept de ces boutons étaient des liens — il rend maintenant les deux.
     */
    public function test_action_buttons_go_through_their_component(): void
    {
        // Quatre écritures à la main subsistent, et pour trois raisons
        // différentes qu'il vaut mieux nommer que noyer :
        $tolerees = [
            // Géométrie propre : une pastille de navigation sans rembourrage
            // vertical, dimensionnée par sa cible tactile de 44 px.
            'layouts/navigation.blade.php',
            // Le bouton de rédaction assistée porte des liaisons Alpine
            // (`@click`, `:disabled`) : `:disabled` sur un composant Blade
            // serait lu comme une expression PHP, et « working || ! notes… »
            // n'en est pas une.
            'provider/services/form.blade.php',
            // Celui-ci n'est pas justifié, il est différé : l'accueil vient
            // d'être refait d'après la maquette amont, et ses deux appels à
            // l'action y ont été mesurés. Les ramener au composant demande de
            // vérifier d'abord que la maquette ne dit pas autre chose — ses
            // deux rembourrages diffèrent pourtant l'un de l'autre.
            'home.blade.php',
        ];

        $fautifs = [];

        foreach ($this->vues() as $vue) {
            if (in_array($vue, $tolerees, true)) {
                continue;
            }

            foreach (explode("\n", $this->contenuSansCommentaires($vue)) as $numero => $ligne) {
                if (! preg_match('/class="([^"]*)"/', $ligne, $m)) {
                    continue;
                }

                $classes = $m[1];

                // La signature d'un bouton d'action : la pilule, le fond de
                // marque, le texte qui va dessus, et la police des boutons.
                // Une pastille de statut n'a pas la dernière ; un bouton
                // d'icône n'a pas la troisième.
                $signature = str_contains($classes, 'rounded-full')
                    && str_contains($classes, 'bg-primary')
                    && str_contains($classes, 'text-on-primary')
                    && str_contains($classes, 'font-button-text');

                if ($signature) {
                    $fautifs[] = $vue.':'.($numero + 1);
                }
            }
        }

        $this->assertSame([], $fautifs,
            'Bouton d\'action écrit à la main plutôt que par x-primary-button : '.implode(', ', $fautifs));
    }

    public function test_depth_goes_through_the_three_elevation_steps(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            preg_match_all('/\bshadow-\[?[a-z0-9_().,\/-]*\]?/', $this->contenuSansCommentaires($vue), $m);

            foreach ($m[0] as $classe) {
                // `hover:shadow-elevation-2` arrive ici sans son préfixe : le
                // motif ne capture que la classe.
                if (! in_array($classe, self::EXCEPTIONS['ombre'], true)) {
                    $fautifs[] = $vue.' ('.$classe.')';
                }
            }
        }

        // La profondeur se lit sur trois paliers tonaux, pas sur une valeur
        // choisie au cas par cas. La maquette amont écrivait six fois
        // `shadow-[0_8px_24px_rgba(0,0,0,0.12)]` en clair, à deux opacités
        // différentes pour le même rôle.
        $this->assertSame([], $fautifs,
            'Ombre hors des trois paliers : '.implode(', ', $fautifs));
    }

    public function test_nothing_lifts_on_hover(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            // `translate-x` sur un pictogramme est toléré : la flèche d'un
            // lien avance de deux pixels *à l'intérieur* de la zone survolée,
            // sans rien déplacer autour d'elle.
            //
            // L'ombre, elle, n'est plus refusée : la charte simule le
            // soulèvement en passant d'`elevation-1` à `elevation-2`, ce qui
            // ne déplace rien. Ce que la règle refuse reste la géométrie —
            // agrandissement et montée. La maquette amont ajoutait
            // `hover:-translate-y-1` par-dessus l'ombre, ce que sa propre
            // charte ne demande nulle part.
            if (preg_match('/(hover|group-hover|focus):(scale-|-?translate-y-)/', $this->contenu($vue))) {
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

    /**
     * Les lignes qui portent une icône, écartées des règles typographiques.
     *
     * @return array<int, string>
     */
    private function lignesSansIcone(string $relatif): array
    {
        $lignes = explode("\n", $this->contenuSansCommentaires($relatif));

        return array_filter($lignes, function (string $ligne): bool {
            foreach (self::EXCEPTIONS['icone'] as $porteur) {
                if (str_contains($ligne, $porteur)) {
                    return false;
                }
            }

            return true;
        });
    }

    public function test_no_text_size_is_written_in_raw_pixels(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            foreach ($this->lignesSansIcone($vue) as $numero => $ligne) {
                if (preg_match('/\btext-\[\d+px\]/', $ligne, $m)) {
                    $fautifs[] = $vue.':'.($numero + 1).' ('.$m[0].')';
                }
            }
        }

        // Quatorze corps écrits à la main — 10, 11, 12, 13, 14, 16, 18, 20,
        // 22, 28, 30, 32, 40 et 120 px — pour ce que la table nomme en sept
        // paliers. Chacun avait sa raison locale ; ensemble ils faisaient une
        // échelle que personne n'a dessinée.
        $this->assertSame([], $fautifs,
            'Corps de texte écrit en pixels, hors de la table : '.implode(', ', $fautifs));
    }

    public function test_text_sizes_come_from_the_named_scale(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            foreach ($this->lignesSansIcone($vue) as $numero => $ligne) {
                // L'échelle générique de Tailwind — `text-sm`, `text-2xl` —
                // dit une taille ; celle de la charte dit un rôle. Les deux
                // rendent les mêmes pixels sur les quatre premiers paliers,
                // et c'est bien le danger : la dérive ne se voit pas.
                if (preg_match('/\btext-(xs|sm|base|lg|xl|2xl|3xl|4xl|5xl|6xl|7xl)\b/', $ligne, $m)) {
                    $fautifs[] = $vue.':'.($numero + 1).' ('.$m[0].')';
                }
            }
        }

        $this->assertSame([], $fautifs,
            'Taille hors de l\'échelle nommée : '.implode(', ', $fautifs));
    }

    public function test_monospace_is_kept_for_figures(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            foreach (explode("\n", $this->contenuSansCommentaires($vue)) as $numero => $ligne) {
                if (! str_contains($ligne, 'font-label-numeric')) {
                    continue;
                }

                // Une famille de titre ou de corps posée sur la même ligne que
                // la chasse fixe : l'une des deux ne s'applique pas, et on ne
                // sait pas laquelle en lisant.
                if (preg_match('/\bfont-(headline-(xl|lg|md|sm)|body-(lg|md)|label-(lg|md)|button-text)\b/', $ligne, $m)) {
                    $fautifs[] = $vue.':'.($numero + 1).' ('.$m[0].')';
                }
            }
        }

        // La chasse fixe est pour les chiffres : prix, dates, références,
        // compteurs. Composé en JetBrains Mono, un mot ouvre un blanc de deux
        // caractères devant chaque valeur et se lit comme une donnée.
        $this->assertSame([], $fautifs,
            'Chasse fixe et police de texte sur la même ligne : '.implode(', ', $fautifs));
    }

    public function test_icons_go_through_their_component(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            if ($vue === 'components/icon.blade.php') {
                continue;
            }

            $contenu = $this->contenuSansCommentaires($vue);

            // Une ligature Material Symbols posée à la main est du texte que
            // rien n'annonce et que rien ne dimensionne : trente icônes du
            // dépôt n'avaient aucun attribut ARIA, et treize corps différents
            // circulaient pour la même famille d'objets.
            if (str_contains($contenu, 'material-symbols-outlined')) {
                $fautifs[] = $vue.' (span brut)';
            }

            // `font-variation-settings` en attribut `style` remet au passage
            // les trois autres axes à leur valeur par défaut, sans le dire.
            if (str_contains($contenu, 'font-variation-settings')) {
                $fautifs[] = $vue.' (axe en style en ligne)';
            }
        }

        $this->assertSame([], $fautifs,
            'Icône hors du composant : '.implode(', ', $fautifs));
    }
}
