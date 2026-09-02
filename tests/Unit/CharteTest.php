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
            // Même raison, retournée : les pages 500 et 503 servent au moment
            // où l'on ne sait pas ce qui est cassé, ou pendant qu'on le
            // répare. Elles ne vont rien chercher ailleurs — ni feuille de
            // style, ni session, ni base — parce que tout cela fait partie de
            // ce qui peut manquer à ce moment-là. Les deux partagent ce
            // document, qui porte donc seul les couleurs en clair.
            'errors/partials/autonome.blade.php',
            // `theme-color` est une balise meta : elle prend une couleur, pas
            // une classe.
            'partials/pwa-head.blade.php',
        ],
        // Une icône n'est pas du texte : sa taille est une dimension, prise
        // en `font-size` par la police de glyphes. Les porteurs d'icône du
        // dépôt sont donc hors de l'échelle typographique.
        'icone' => [
            'icone-taille-',
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
        // Deux écritures à la main subsistent, chacune pour une raison
        // qu'il vaut mieux nommer que noyer :
        $tolerees = [
            // Géométrie propre : une pastille de navigation sans rembourrage
            // vertical, dimensionnée par sa cible tactile de 44 px.
            'layouts/navigation.blade.php',
            // Le bouton de rédaction assistée porte des liaisons Alpine
            // (`@click`, `:disabled`) : `:disabled` sur un composant Blade
            // serait lu comme une expression PHP, et « working || ! notes… »
            // n'en est pas une.
            'provider/services/form.blade.php',
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

    /**
     * Une seule écriture pour `@php`, et ce n'est pas une question de goût.
     *
     * Blade extrait d'abord les blocs `@php … @endphp` du fichier, avec un
     * motif non gourmand qui part du premier `@php` rencontré. La forme
     * courte `@php(...)` en est un : mêlée à un bloc situé plus bas, elle en
     * devient l'ouverture, et tout ce qui les sépare est recopié tel quel dans
     * la vue compilée — directives comprises.
     *
     * Rien ne le signale. Le fichier compile, la page rend, et l'erreur qui
     * finit par sortir désigne une variable indéfinie dans une boucle qui n'a
     * jamais été compilée. C'est arrivé sur « Mes services », où la moitié de
     * l'écran est sortie en texte brut.
     */
    public function test_php_directives_keep_a_single_form(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            if (preg_match('/@php\s*\(/', $this->contenu($vue))) {
                $fautifs[] = $vue;
            }
        }

        $this->assertSame([], $fautifs,
            'Forme courte `@php(...)` : employer `@php … @endphp` — '.implode(', ', $fautifs));
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

    /**
     * Le troisième rouge de la charte n'habille rien.
     *
     * La palette du drapeau donne trois couleurs de marque, que le schéma
     * amont nomme `primary`, `secondary` et `tertiary`. Les deux premières ont
     * un rôle : le vert pour l'action, le jaune pour l'attente. La troisième
     * est un rouge — `#aa001a`, à dix points de l'`error` `#ba1a1a`. Deux
     * rouges pour un seul travail, dont l'un ne se distingue de l'autre sur
     * aucun écran.
     *
     * Elle portait l'ambre avant la bascule, et tout ce qui devait « attirer
     * l'attention sans alarmer » s'en était servi. Après la bascule, ces
     * quinze emplois disaient panne là où ils disaient échéance : « Envoyée »
     * et « Refusée » portaient la même pastille à un point de teinte près,
     * « En cours » s'affichait en rouge plein, le bandeau d'essai gratuit
     * s'ouvrait sur un rose d'alerte, et les étoiles du formulaire d'avis
     * étaient rouges quand celles qui affichent la même note sont jaunes.
     *
     * Le jeton reste dans la table : il appartient au schéma amont, et un
     * schéma troué se désynchronise du premier écran qu'on y réimportera.
     * C'est son emploi qui est refusé, pas son existence.
     */
    public function test_the_third_red_is_not_used(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            if (preg_match('/\b(bg|text|border|from|to|via|ring|divide)-(on-)?tertiary/', $this->contenuSansCommentaires($vue), $m)) {
                $fautifs[] = $vue.' ('.$m[0].')';
            }
        }

        $this->assertSame([], $fautifs,
            'Le jeton `tertiary` n\'habille rien : le jaune pour l\'attente, l\'`error` pour la panne — '
            .implode(', ', $fautifs));
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

    /**
     * Une étoile n'est pas un caractère.
     *
     * « ★ » (U+2605) écrit en clair dans une vue tombe dans la police du
     * paragraphe qui l'accueille. Sur une ligne en chasse fixe — un prix, une
     * note — JetBrains Mono ne le porte pas : le navigateur va en chercher un
     * autre, d'une autre famille et d'une autre chasse, et à 390 px le glyphe
     * finissait seul sur sa ligne, sous la note qu'il devait accompagner.
     *
     * La note a son composant, `x-star-rating`, et l'étoile son pictogramme.
     */
    public function test_stars_are_drawn_not_typed(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            if (preg_match('/[\x{2605}\x{2606}]/u', $this->contenuSansCommentaires($vue))) {
                $fautifs[] = $vue;
            }
        }

        $this->assertSame([], $fautifs,
            'Étoile écrite en clair : employer x-star-rating ou <x-icon name="star" /> — '
            .implode(', ', $fautifs));
    }

    /**
     * Un fichier déposé peut toujours manquer à l'appel : le disque a changé,
     * le seau a été vidé, le déploiement a emporté storage/. Le navigateur
     * remplace alors l'image par son pictogramme de fichier cassé — un signe
     * de panne, là où la vue a presque toujours une initiale ou une scène
     * dessinée à montrer. `onerror` la retire pour laisser reparaître ce qui
     * est dessous.
     *
     * Le passage de `MEDIA_DISK=s3` à `public` fait exactement cela sur toutes
     * les images déjà déposées, d'un seul réglage.
     */
    public function test_uploaded_images_survive_a_missing_file(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            $contenu = $this->contenuSansCommentaires($vue);

            // Les expressions Blade portent des « -> » : leurs chevrons
            // fermeraient la balise avant d'avoir vu ses derniers attributs,
            // et la règle croirait `onerror` absent alors qu'il est écrit.
            $plat = preg_replace_callback(
                '/\{\{.*?\}\}|@if\s*\(.*?\)/s',
                fn (array $m) => str_repeat(' ', strlen($m[0])),
                $contenu,
            );

            preg_match_all('/<img\b[^>]*>/s', (string) $plat, $balises, PREG_OFFSET_CAPTURE);

            foreach ($balises[0] as [$vide, $decalage]) {
                $balise = substr($contenu, $decalage, strlen($vide));

                if (str_contains($balise, 'media_url(') && ! str_contains($balise, 'onerror')) {
                    $fautifs[] = $vue;
                }
            }
        }

        $this->assertSame([], array_unique($fautifs),
            'Image déposée sans onerror : ajouter onerror="this.remove()" pour laisser reparaître le repli — '
            .implode(', ', array_unique($fautifs)));
    }

    /**
     * Le guillemet échappé n'existe pas en Blade.
     *
     * Un attribut de composant est délimité par ses guillemets : `\"` y ferme
     * la valeur au milieu de l'expression, et la vue compilée devient du PHP
     * invalide — « unexpected token endif », dans un fichier de cache dont le
     * nom ne dit pas de quelle vue il vient. La page ne rend pas une erreur de
     * syntaxe : elle rend 500. Une page 404 répondant 500, en l'occurrence.
     *
     * Le remède est le même partout : sortir la chaîne dans un bloc `@php` et
     * passer la variable.
     */
    /**
     * Une couleur écrite en dur ne suit pas le schéma sombre.
     *
     * C'est le défaut propre au mode sombre, et il ne se voit qu'en sombre :
     * la page passe au noir, et l'élément resté en dur garde sa couleur
     * claire. Sept valeurs de `app.css` étaient dans ce cas — la sélection de
     * texte, le curseur de saisie, la barre de défilement, l'anneau de focus —
     * et trente et une scènes de métier restaient des rectangles clairs au
     * milieu d'une page noire.
     *
     * Les feuilles autonomes sont hors de cette règle : elles servent au
     * moment où les jetons peuvent manquer, et portent leur propre bloc
     * `prefers-color-scheme`.
     */
    public function test_no_stylesheet_hardcodes_a_colour(): void
    {
        $fautifs = [];

        foreach (['app.css', 'icones.css'] as $feuille) {
            $chemin = dirname(__DIR__, 2).'/resources/css/'.$feuille;

            if (! file_exists($chemin)) {
                continue;
            }

            $contenu = preg_replace('~/\*.*?\*/~s', '', (string) file_get_contents($chemin)) ?? '';

            if (preg_match_all('/#[0-9a-fA-F]{3,8}\b/', $contenu, $m)) {
                $fautifs[] = $feuille.' ('.implode(', ', array_unique($m[0])).')';
            }
        }

        $this->assertSame([], $fautifs,
            'Couleur en dur dans une feuille : passer par rgb(var(--jeton)), sans quoi elle ne suivra pas le schéma sombre — '
            .implode(' ; ', $fautifs));
    }

    /**
     * Les deux schémas définissent les mêmes jetons.
     *
     * Un jeton défini en clair et oublié en sombre garde sa valeur claire :
     * un seul mot de couleur reste alors lumineux au milieu d'une page noire,
     * et rien ne le signale.
     */
    public function test_both_schemes_define_the_same_tokens(): void
    {
        $jetons = (string) file_get_contents(dirname(__DIR__, 2).'/resources/css/jetons.css');

        preg_match_all('/^:root \{(.*?)^\}/ms', $jetons, $clair);
        preg_match_all('/:root\[data-theme=\x27dark\x27\] \{(.*?)^\}/ms', $jetons, $sombre);

        $nomsDe = fn (string $bloc) => array_values(array_unique(
            preg_match_all('/--([a-z0-9-]+):/', $bloc, $m) ? $m[1] : []
        ));

        $enClair = $nomsDe($clair[1][0] ?? '');
        $enSombre = $nomsDe($sombre[1][0] ?? '');

        // Les jetons « fixed » de Material 3 ne changent pas d'un schéma à
        // l'autre : c'est leur définition, pas un oubli.
        $attendus = array_values(array_filter($enClair, fn (string $n) => ! str_contains($n, 'fixed')));

        $this->assertNotEmpty($enSombre, 'Le bloc sombre explicite est introuvable dans jetons.css.');
        $this->assertSame([], array_values(array_diff($attendus, $enSombre)),
            'Jeton défini en clair et oublié en sombre : il gardera sa valeur claire.');
    }

    /**
     * Rien n'est chargé depuis un CDN tiers.
     *
     * Le dépôt héberge ses polices pour trois raisons — une requête bloquante
     * de moins, une adresse IP de visiteur qui ne part pas chez un tiers, et
     * une page qui ne dépend pas d'un serveur qu'on ne tient pas. Leaflet
     * venait pourtant d'unpkg.com sur les deux écrans qui portent une carte,
     * et rendait un `ReferenceError: L is not defined` en pleine page dès que
     * ce CDN n'était pas joignable.
     *
     * Les tuiles de carte sont l'exception, et la seule : une carte sans
     * serveur de tuiles n'est pas une carte.
     */
    public function test_no_view_loads_from_a_third_party_cdn(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            $contenu = $this->contenuSansCommentaires($vue);

            preg_match_all('/(?:src|href)="(https?:\/\/[^"]+)"/', $contenu, $m);

            foreach ($m[1] as $url) {
                if (str_contains($url, 'tile.openstreetmap.org')) {
                    continue;
                }

                $fautifs[] = $vue.' ('.$url.')';
            }
        }

        $this->assertSame([], $fautifs,
            'Ressource chargée depuis un tiers : l\'héberger, comme les polices — '.implode(', ', $fautifs));
    }

    public function test_no_escaped_quote_survives_in_a_view(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            if (str_contains($this->contenuSansCommentaires($vue), '\\"')) {
                $fautifs[] = $vue;
            }
        }

        $this->assertSame([], $fautifs,
            'Guillemet échappé dans une vue : sortir la chaîne dans un bloc @php — '
            .implode(', ', $fautifs));
    }

    public function test_icons_go_through_their_component(): void
    {
        $fautifs = [];

        foreach ($this->vues() as $vue) {
            if ($vue === 'components/icon.blade.php') {
                continue;
            }

            $contenu = $this->contenuSansCommentaires($vue);

            // Une classe de glyphe posée à la main court-circuite la table de
            // correspondance : elle fige un nom Font Awesome dans une vue, et
            // le jour où l'on change de fonte, cette icône-là reste seule en
            // arrière. Elle échappe aussi au relevé, donc au sous-ensemble de
            // la police — le pictogramme manque alors sans la moindre erreur.
            if (preg_match('/class="[^"]*\bicone-(?!taille-|contour\b)[a-z]/', $contenu)) {
                $fautifs[] = $vue.' (classe de glyphe en dur)';
            }

            // La forme précédente : une ligature Material Symbols écrite à la
            // main, qui était du texte que rien n'annonçait et que rien ne
            // dimensionnait.
            if (str_contains($contenu, 'material-symbols') || str_contains($contenu, 'font-variation-settings')) {
                $fautifs[] = $vue.' (reste de Material Symbols)';
            }

            // Font Awesome écrit d'ordinaire ses icônes ainsi. Ici, le
            // composant est le seul chemin : une balise `<i class="fa-...">`
            // dans une vue signifie que quelqu'un a contourné la table.
            if (preg_match('/<i\s[^>]*class="[^"]*\bfa[-s]/', $contenu)) {
                $fautifs[] = $vue.' (balise Font Awesome brute)';
            }
        }

        $this->assertSame([], $fautifs,
            'Icône hors du composant : '.implode(', ', $fautifs));
    }
}
