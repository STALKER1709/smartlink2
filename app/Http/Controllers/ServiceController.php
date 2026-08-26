<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Ai\SearchIntentExtractor;
use App\Services\SearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
            // Les concurrents d'abord. « Services similaires » n'a d'intérêt
            // que s'il permet de comparer : rempli par les autres annonces du
            // même prestataire, il ne propose aucun choix et ne mérite pas son
            // titre. Ses annonces restent en repli quand personne d'autre
            // n'exerce le métier dans la base.
            ->orderByRaw('case when provider_id = ? then 1 else 0 end', [$service->provider_id])
            ->orderByDesc('views_count')
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

        $intent = $this->guestAllowance($request)
            ? $this->intents->extract($query, $request->user())
            : null;

        if ($intent === null || $intent->isEmpty()) {
            return redirect()->route('services.index', ['term' => $query]);
        }

        return redirect()
            ->route('services.index', $intent->toQueryParameters())
            ->with('searchIntent', $intent->summary());
    }

    /**
     * La recherche en langage naturel est ouverte aux visiteurs : c'est elle
     * qui donne envie de créer un compte. Mais la page est publique et sans
     * limite, donc le plafond par compte ne protège plus rien : il est
     * remplacé par un plafond quotidien par adresse. Au-delà, on retombe
     * silencieusement sur la recherche par mot-clé, comme pour tout autre
     * refus.
     */
    private function guestAllowance(Request $request): bool
    {
        if ($request->user() !== null) {
            return true;
        }

        $perDay = (int) config('ai.limits.guest_searches_per_day');

        if ($perDay <= 0) {
            return false;
        }

        $key = 'ai-search:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, $perDay)) {
            return false;
        }

        RateLimiter::hit($key, decaySeconds: 60 * 60 * 24);

        return true;
    }
}
