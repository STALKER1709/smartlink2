<?php

namespace App\Http\Controllers;

use App\Models\ProviderProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        return view('favorites.index', [
            'favorites' => $request->user()
                ->favorites()
                ->with(['user', 'category'])
                ->withCount(['services' => fn ($q) => $q->where('status', 'active')])
                ->orderByDesc('favorites.created_at')
                ->paginate(12),
        ]);
    }

    /**
     * Le cœur bascule : un même bouton ajoute et retire.
     *
     * `toggle()` sur la relation plutôt qu'un `create()` conditionnel — la
     * contrainte d'unicité de la table protège des doubles clics, mais
     * l'attacher deux fois lèverait une exception qu'un visiteur ne doit
     * jamais voir.
     */
    public function toggle(Request $request, ProviderProfile $providerProfile): RedirectResponse
    {
        $resultat = $request->user()->favorites()->toggle($providerProfile->id);

        return back()->with('status', $resultat['attached'] !== []
            ? $providerProfile->business_name.' est dans vos favoris.'
            : $providerProfile->business_name.' a été retiré de vos favoris.');
    }
}
