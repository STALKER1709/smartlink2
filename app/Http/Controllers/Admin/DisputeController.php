<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisputeController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function index(Request $request): View
    {
        $statut = $request->string('status')->toString();

        return view('admin.disputes.index', [
            'disputes' => Dispute::query()
                ->when($statut !== '', fn ($q) => $q->where('status', $statut))
                ->with(['request.service', 'reporter', 'reviewer'])
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'comptes' => Dispute::query()
                ->getQuery()
                ->select('status')
                ->selectRaw('count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all(),
        ]);
    }

    /**
     * Trancher ferme le signalement et garde la décision écrite : « résolu »
     * sans un mot ne dit rien à qui l'a déposé.
     */
    public function resolve(Request $request, Dispute $dispute): RedirectResponse
    {
        $this->authorize('resolve', $dispute);

        $donnees = $request->validate([
            'status' => ['required', 'in:'.Dispute::STATUS_RESOLVED.','.Dispute::STATUS_REJECTED],
            'resolution' => ['required', 'string', 'max:1000'],
        ], [
            'resolution.required' => 'Écrivez ce qui a été décidé : le déclarant recevra ce texte.',
        ]);

        /*
         * `forceFill` et non `update` : ces quatre colonnes ne figurent pas
         * dans `Fillable`, et c'est voulu — la décision d'un administrateur ne
         * doit jamais être assignable en masse depuis une requête. `update()`
         * les écartait en silence : le message disait « clos » pendant que la
         * ligne restait ouverte.
         */
        $dispute->forceFill([
            'status' => $donnees['status'],
            'resolution' => $donnees['resolution'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        $this->auditLog->log($request->user(), 'dispute.'.$donnees['status'], $dispute, [
            'request_id' => $dispute->request_id,
        ]);

        return back()->with('status', 'Signalement n° '.$dispute->id.' clos.');
    }
}
