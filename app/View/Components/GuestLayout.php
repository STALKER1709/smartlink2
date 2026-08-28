<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * @param  string|null  $titre  Le titre propre à la page, sans le nom du
     *                              site. Voir `AppLayout`.
     * @param  bool  $indexable  La plupart des écrans d'authentification n'ont
     *                           rien à faire dans un index ; l'inscription, si.
     */
    public function __construct(
        public ?string $titre = null,
        public ?string $description = null,
        public bool $indexable = false,
    ) {}

    public function render(): View
    {
        return view('layouts.guest');
    }
}
