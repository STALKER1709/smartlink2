<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceCategoryRequest;
use App\Http\Requests\Admin\UpdateServiceCategoryRequest;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServiceCategoryController extends Controller
{
    public function index(): View
    {
        $categories = ServiceCategory::query()
            ->withCount([
                'services',
                'services as active_services_count' => fn ($q) => $q->where('status', Service::STATUS_ACTIVE),
            ])
            ->orderBy('name')
            ->paginate(20);

        // Les trois chiffres de la maquette. Le prix moyen porte la mention
        // « indicatif » partout où il paraît : SmartLink n'encaisse rien, et
        // ce que les prestataires affichent n'engage qu'eux.
        return view('admin.categories.index', [
            'categories' => $categories,
            'totalCategories' => ServiceCategory::query()->count(),
            'prestatairesActifs' => ProviderProfile::query()
                ->whereHas('user', fn ($q) => $q->where('status', User::STATUS_ACTIVE))
                ->count(),
            'prixMoyen' => (int) round((float) Service::query()
                ->where('status', Service::STATUS_ACTIVE)
                ->whereNotNull('price_amount')
                ->avg('price_amount')),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(StoreServiceCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        ServiceCategory::create($data);

        return redirect()->route('admin.categories.index')->with('status', 'Catégorie créée avec succès.');
    }

    public function edit(ServiceCategory $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $category): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('status', 'Catégorie mise à jour avec succès.');
    }

    public function destroy(ServiceCategory $category): RedirectResponse
    {
        $category->delete();

        return back()->with('status', 'Catégorie supprimée.');
    }
}
