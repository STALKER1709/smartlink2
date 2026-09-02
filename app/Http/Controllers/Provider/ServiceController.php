<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreServiceRequest;
use App\Http\Requests\Provider\UpdateServiceRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $base = $request->user()->services();

        /*
         * Les trois onglets de la maquette. « Actif » n'est pas une colonne :
         * une annonce travaille quand elle est publiée *et* disponible. Un
         * prestataire qui décoche « disponible » le temps d'un déplacement
         * s'attend à la retrouver en pause, pas parmi les actives.
         */
        $compteurs = [
            'tous' => (clone $base)->count(),
            'actifs' => (clone $base)->where('status', Service::STATUS_ACTIVE)->where('is_available', true)->count(),
        ];
        $compteurs['pause'] = $compteurs['tous'] - $compteurs['actifs'];

        $statut = in_array($request->query('statut'), ['actifs', 'pause'], true)
            ? $request->query('statut')
            : 'tous';

        $services = $base
            ->when($statut === 'actifs', fn ($q) => $q->where('status', Service::STATUS_ACTIVE)->where('is_available', true))
            ->when($statut === 'pause', fn ($q) => $q->where(fn ($w) => $w
                ->where('status', '!=', Service::STATUS_ACTIVE)
                ->orWhere('is_available', false)))
            ->with(['category', 'images'])
            // Le seul chiffre qui dise si une annonce travaille.
            ->withCount('requests')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('provider.services.index', [
            'services' => $services,
            'compteurs' => $compteurs,
            'statut' => $statut,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Service::class);

        return view('provider.services.create', [
            'categories' => ServiceCategory::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $this->authorize('create', Service::class);

        $service = $request->user()->services()->create([
            ...$request->safe()->except(['images', 'is_available']),
            'is_available' => $request->boolean('is_available'),
            'status' => Service::STATUS_ACTIVE,
        ]);

        $this->storeImages($service, $request->file('images', []));

        return redirect()->route('provider.services.index')
            ->with('status', 'Service publié avec succès.');
    }

    public function edit(Service $service): View
    {
        $this->authorize('update', $service);

        return view('provider.services.edit', [
            'service' => $service->load('images'),
            'categories' => ServiceCategory::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        $service->update([
            ...$request->safe()->except(['images', 'remove_images', 'is_available']),
            'is_available' => $request->boolean('is_available'),
        ]);

        foreach ($request->validated('remove_images', []) as $imageId) {
            /** @var ServiceImage|null $image */
            $image = $service->images()->whereKey($imageId)->first();
            if ($image) {
                Storage::disk(media_disk())->delete($image->path);
                $image->delete();
            }
        }

        $this->storeImages($service, $request->file('images', []));

        return redirect()->route('provider.services.index')
            ->with('status', 'Service mis à jour avec succès.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->authorize('delete', $service);

        $service->delete();

        return redirect()->route('provider.services.index')
            ->with('status', 'Service supprimé.');
    }

    /**
     * @param  array<int, UploadedFile>  $images
     */
    private function storeImages(Service $service, array $images): void
    {
        $position = $service->images()->max('position') ?? 0;

        foreach ($images as $image) {
            $position++;
            $service->images()->create([
                'path' => $image->store('services', media_disk()),
                'position' => $position,
            ]);
        }
    }
}
