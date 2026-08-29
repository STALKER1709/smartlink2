<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * @param  bool  $assistant  L'assistant flotte sur toutes les pages sauf
     *                           celles où il gêne : sur un écran de discussion,
     *                           sa bulle verte se pose à côté du bouton d'envoi,
     *                           lui aussi rond et vert.
     * @param  bool  $piedDePage  Un écran de conversation ne défile pas jusqu'à
     *                            un pied de page de site.
     * @param  string|null  $titre  Le titre propre à la page, sans le nom du
     *                              site. Sans lui, toutes les pages portent le
     *                              même titre : ni un moteur ni un onglet ne
     *                              les distinguent.
     * @param  string|null  $description  Le résumé affiché sous le lien dans
     *                                    les résultats de recherche et dans les
     *                                    aperçus de partage.
     * @param  string|null  $imagePartage  L'aperçu composé par WhatsApp et
     *                                     Facebook. À défaut, le visuel de
     *                                     marque.
     * @param  bool  $indexable  Les écrans privés — tableau de bord, demandes,
     *                           messagerie — n'ont rien à faire dans un index.
     */
    public function __construct(
        public bool $assistant = true,
        public bool $piedDePage = true,
        public ?string $titre = null,
        public ?string $description = null,
        public ?string $imagePartage = null,
        public bool $indexable = true,
    ) {}

    public function render(): View
    {
        return view('layouts.app');
    }
}
