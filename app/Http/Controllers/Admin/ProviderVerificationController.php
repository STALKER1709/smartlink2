<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderVerificationController extends Controller
{
    public function __construct(private readonly SmsService $sms) {}

    public function index(): View
    {
        $pending = ProviderProfile::query()
            ->whereNotNull('id_card_path')
            ->where('id_card_verified', false)
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('admin.verification.index', ['pending' => $pending]);
    }

    public function approve(Request $request, ProviderProfile $providerProfile): RedirectResponse
    {
        $providerProfile->update([
            'is_verified' => true,
            'id_card_verified' => true,
        ]);

        if ($providerProfile->user->phone) {
            $this->sms->send(
                $providerProfile->user->phone,
                'SmartLink : Votre profil prestataire a été vérifié. Le badge "Vérifié" est maintenant visible sur votre profil.'
            );
        }

        return back()->with('status', 'Prestataire vérifié avec succès.');
    }

    public function reject(Request $request, ProviderProfile $providerProfile): RedirectResponse
    {
        $providerProfile->update(['id_card_path' => null]);

        if ($providerProfile->user->phone) {
            $this->sms->send(
                $providerProfile->user->phone,
                'SmartLink : Votre pièce d\'identité n\'a pas pu être validée. Veuillez soumettre un document lisible et valide.'
            );
        }

        return back()->with('status', 'Document rejeté.');
    }
}
