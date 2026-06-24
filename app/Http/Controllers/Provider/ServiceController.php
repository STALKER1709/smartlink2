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
        $services = $request->user()->services()
            ->with(['category', 'images'])
            ->latest()
            ->paginate(10);

        return view('provider.services.index', [
            'services' => $services,
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
                Storage::disk('public')->delete($image->path);
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
                'path' => $image->store('services', 'public'),
                'position' => $position,
            ]);
        }
    }
}
