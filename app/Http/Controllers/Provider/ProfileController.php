<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\UpdateProviderProfileRequest;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $providerProfile = $request->user()->providerProfile;

        $this->authorize('update', $providerProfile);

        return view('provider.profile.edit', [
            'providerProfile' => $providerProfile->load('category'),
            'categories' => ServiceCategory::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProviderProfileRequest $request): RedirectResponse
    {
        $providerProfile = $request->user()->providerProfile;

        $this->authorize('update', $providerProfile);

        $data = $request->safe()->except(['logo', 'id_card']);

        if ($request->hasFile('logo')) {
            if ($providerProfile->logo_path) {
                Storage::disk(media_disk())->delete($providerProfile->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('logos', media_disk());
        }

        if ($request->hasFile('id_card')) {
            if ($providerProfile->id_card_path) {
                Storage::disk(media_disk())->delete($providerProfile->id_card_path);
            }
            $data['id_card_path'] = $request->file('id_card')->store('id_cards', media_disk());
            $data['id_card_verified'] = false;
        }

        $providerProfile->update($data);

        return back()->with('status', 'Profil prestataire mis à jour avec succès.');
    }
}
