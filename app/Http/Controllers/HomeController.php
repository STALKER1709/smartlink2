<?php

namespace App\Http\Controllers;

use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        // Une catégorie sans aucun service est une impasse : on la compte pour
        // pouvoir l'afficher honnêtement, et pour ranger les plus fournies en
        // tête plutôt que par ordre alphabétique.
        $categories = ServiceCategory::query()
            ->active()
            ->withCount(['services' => fn ($query) => $query->active()->available()->fromListedProvider()])
            ->orderByDesc('services_count')
            ->orderBy('name')
            ->get();

        $recentServices = Service::query()
            ->active()
            ->available()
            ->fromListedProvider()
            ->with(['provider.providerProfile', 'category', 'images'])
            ->latest()
            ->take(8)
            ->get();

        // La preuve sociale de la page d'accueil : des prestataires vérifiés,
        // les mieux notés d'abord. Le palier Pro remonte à note égale.
        $featuredProviders = ProviderProfile::query()
            ->listed()
            ->verified()
            ->with('category')
            ->withCount(['services' => fn ($query) => $query->active()->available()])
            ->orderByDesc('is_promoted')
            ->orderByDesc('rating_avg')
            ->orderByDesc('rating_count')
            ->take(4)
            ->get();

        return view('home', [
            'categories' => $categories,
            'recentServices' => $recentServices,
            'featuredProviders' => $featuredProviders,
            'providerCount' => ProviderProfile::query()->listed()->count(),
            'serviceCount' => Service::query()->active()->available()->fromListedProvider()->count(),
        ]);
    }
}
