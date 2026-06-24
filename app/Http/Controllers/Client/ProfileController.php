<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\UpdateClientProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $clientProfile = $request->user()->clientProfile;

        $this->authorize('update', $clientProfile);

        return view('client.profile.edit', [
            'clientProfile' => $clientProfile,
        ]);
    }

    public function update(UpdateClientProfileRequest $request): RedirectResponse
    {
        $clientProfile = $request->user()->clientProfile;

        $this->authorize('update', $clientProfile);

        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            if ($clientProfile->photo_path) {
                Storage::disk('public')->delete($clientProfile->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('avatars', 'public');
        }

        $clientProfile->update($data);

        return back()->with('status', 'Profil mis à jour avec succès.');
    }
}
