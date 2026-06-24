<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(private readonly SearchService $searchService)
    {
    }

    public function index(Request $request): View
    {
        $services = $this->searchService->searchServices($request->only([
            'category_id', 'city', 'term', 'available_only', 'sort',
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

        $service->load(['provider.providerProfile', 'category', 'images']);

        $relatedServices = Service::query()
            ->active()
            ->available()
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
}
