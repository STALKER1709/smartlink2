<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDisputeRequest;
use App\Models\Dispute;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class DisputeController extends Controller
{
    public function create(ServiceRequest $serviceRequest): View
    {
        $this->authorize('create', [Dispute::class, $serviceRequest]);

        return view('disputes.create', [
            'serviceRequest' => $serviceRequest->load('service', 'provider.providerProfile', 'client'),
        ]);
    }

    public function store(StoreDisputeRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('create', [Dispute::class, $serviceRequest]);

        /*
         * Les fichiers passent par `media_disk()` et jamais par
         * `disk('public')` en dur : c'est ce qui permet de basculer sur S3, et
         * un disque codé en dur fait disparaître les pièces au déploiement.
         */
        $chemins = collect($request->file('evidence', []))
            ->map(fn (UploadedFile $fichier) => $fichier->store('disputes/'.$serviceRequest->id, media_disk()))
            ->all();

        $dispute = Dispute::create([
            'request_id' => $serviceRequest->id,
            'reporter_id' => $request->user()->id,
            'reason' => $request->validated('reason'),
            'description' => $request->validated('description'),
            'evidence_paths' => $chemins ?: null,
        ]);

        return redirect()
            ->route('requests.show', $serviceRequest)
            ->with('status', 'Votre signalement n° '.$dispute->id.' a été transmis. Notre équipe revient vers vous sous 24 heures.');
    }

    /**
     * Sans cet écran, un signalement partirait dans le vide : le déclarant ne
     * saurait jamais ce qu'il est devenu.
     */
    public function index(Request $request): View
    {
        return view('disputes.index', [
            'disputes' => Dispute::query()
                ->where('reporter_id', $request->user()->id)
                ->with('request.service')
                ->latest()
                ->paginate(10),
        ]);
    }
}
