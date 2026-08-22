<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Ai\SearchIntentExtractor;
use App\Services\SearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(
        private readonly SearchService $searchService,
        private readonly SearchIntentExtractor $intents,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->filled('q')) {
            return $this->interpret($request);
        }

        $services = $this->searchService->searchServices($request->only([
            'category_id', 'city', 'quarter', 'term', 'available_only', 'sort',
        ]));

        $categories = ServiceCategory::query()->active()->orderBy('name')->get();

        return view('services.index', [
            'services' => $services,
            'categories' => $categories,
        ]);
    }

    public function show(Service $service): View
    {
        $this->authorize('view', $service);

        $this->abortIfProviderIsHidden($service->provider_id);

        $service->load(['provider.providerProfile', 'category', 'images']);

        $relatedServices = Service::query()
            ->active()
            ->available()
            ->fromListedProvider()
            ->where('category_id', $service->category_id)
            ->whereKeyNot($service->id)
            ->with(['provider.providerProfile', 'images'])
            ->take(4)
            ->get();

        return view('services.show', [
            'service' => $service,
            'relatedServices' => $relatedServices,
        ]);
    }

    /**
     * Traduit une phrase libre en filtres, puis redirige vers la recherche
     * classique. La redirection rend l'URL partageable, garde les filtres
     * visibles et modifiables, et évite de refacturer un rafraîchissement.
     *
     * En cas d'échec — IA coupée, budget épuisé, extraction inexploitable —
     * la phrase redevient un simple mot-clé, sans que rien ne le signale.
     */
    private function interpret(Request $request): RedirectResponse
    {
        $query = mb_substr(trim($request->string('q')->toString()), 0, 300);

        $intent = $this->intents->extract($query, $request->user());

        if ($intent === null || $intent->isEmpty()) {
            return redirect()->route('services.index', ['term' => $query]);
        }

        return redirect()
            ->route('services.index', $intent->toQueryParameters())
            ->with('searchIntent', $intent->summary());
    }
}
