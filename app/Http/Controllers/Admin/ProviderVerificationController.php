<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Diffuse la pièce d'identité déposée.
     *
     * C'est la seule sortie de ces fichiers : le disque qui les porte est
     * privé, rien n'y est atteignable par une URL. La Policy tranche avant
     * toute lecture — l'administrateur qui vérifie, le prestataire qui a
     * déposé, personne d'autre.
     *
     * L'en-tête `inline` laisse le navigateur afficher l'image ou le PDF sans
     * téléchargement, ce que la page de vérification attend ; `private, no-store`
     * empêche tout intermédiaire d'en garder une copie.
     */
    public function document(ProviderProfile $providerProfile): StreamedResponse
    {
        $this->authorize('viewIdDocument', $providerProfile);

        $disk = Storage::disk(id_documents_disk());

        abort_if(
            $providerProfile->id_card_path === null
                || ! $disk->exists($providerProfile->id_card_path),
            404
        );

        return $disk->response($providerProfile->id_card_path, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function reject(Request $request, ProviderProfile $providerProfile): RedirectResponse
    {
        // Le document rejeté est effacé, pas seulement détaché : le laisser sur
        // le disque conserverait une pièce d'identité que plus rien ne référence
        // et que personne ne pense à purger.
        if ($providerProfile->id_card_path !== null) {
            Storage::disk(id_documents_disk())->delete($providerProfile->id_card_path);
        }

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
