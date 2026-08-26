<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Les pages légales. Elles n'ont pas de modèle : ce sont des documents, et
 * leur contenu vit dans les vues. Ce qui ne peut pas être deviné — raison
 * sociale, RCCM, siège — vient de `config/legal.php`, que l'environnement
 * renseigne.
 */
class LegalController extends Controller
{
    public function terms(): View
    {
        return view('legal.conditions');
    }

    public function notice(): View
    {
        return view('legal.mentions');
    }

    public function privacy(): View
    {
        return view('legal.confidentialite');
    }
}
