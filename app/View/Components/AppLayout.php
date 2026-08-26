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
     */
    public function __construct(
        public bool $assistant = true,
        public bool $piedDePage = true,
    ) {}

    public function render(): View
    {
        return view('layouts.app');
    }
}
