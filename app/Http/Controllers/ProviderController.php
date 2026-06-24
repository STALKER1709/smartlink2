<?php

namespace App\Http\Controllers;

use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderController extends Controller
{
    public function __construct(private readonly SearchService $searchService) {}

    public function index(Request $request): View
    {
        $providers = $this->searchService->searchProviders($request->only([
            'category_id', 'city', 'term', 'verified_only',
        ]));

        $categories = ServiceCategory::query()->active()->orderBy('name')->get();

        return view('providers.index', [
            'providers' => $providers,
            'categories' => $categories,
        ]);
    }

    public function show(ProviderProfile $providerProfile): View
    {
        $this->authorize('view', $providerProfile);

        $providerProfile->load('user', 'category');

        $services = $providerProfile->user
            ->services()
            ->active()
            ->with('images')
            ->latest()
            ->paginate(9)
            ->withQueryString();

        $reviews = $providerProfile->user
            ->reviewsReceived()
            ->visible()
            ->with('client.clientProfile')
            ->latest()
            ->take(10)
            ->get();

        return view('providers.show', [
            'providerProfile' => $providerProfile,
            'services' => $services,
            'reviews' => $reviews,
        ]);
    }
}
