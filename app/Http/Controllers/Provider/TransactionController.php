<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $payments = Payment::query()
            ->where('payer_id', $user->id)
            ->with('plan')
            ->latest('created_at')
            ->paginate(15);

        /*
         * La maquette pose trois chiffres en tête : « Gains ce mois »,
         * « En attente » et « Dépenses ». Les deux premiers supposent une
         * place de marché qui encaisse pour le prestataire — SmartLink ne voit
         * jamais un franc de la prestation, et le dire autrement serait faux.
         *
         * Les trois chiffres portent donc sur ce qui existe réellement : les
         * abonnements réglés à SmartLink.
         */
        $base = fn () => Payment::query()->where('payer_id', $user->id);

        return view('provider.transactions.index', [
            'payments' => $payments,
            'regleCetteAnnee' => (int) $base()
                ->where('status', Payment::STATUS_SUCCESS)
                ->where('created_at', '>=', now()->startOfYear())
                ->sum('amount_xaf'),
            'enAttente' => (int) $base()
                ->where('status', Payment::STATUS_PENDING)
                ->sum('amount_xaf'),
            'totalVerse' => (int) $base()
                ->where('status', Payment::STATUS_SUCCESS)
                ->sum('amount_xaf'),
        ]);
    }

    /**
     * L'export des maquettes. Diffusé plutôt qu'accumulé en mémoire : un
     * prestataire de longue date peut avoir des centaines de lignes, et rien
     * n'oblige à toutes les tenir avant d'écrire la première.
     */
    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $nom = 'smartlink-transactions-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($user) {
            $sortie = fopen('php://output', 'wb');

            // Marque d'ordre des octets : sans elle, Excel lit « Réglé »
            // comme « RÃ©glÃ© ».
            fwrite($sortie, "\xEF\xBB\xBF");
            fputcsv($sortie, ['Date', 'Palier', 'Montant (FCFA)', 'Statut', 'Référence', 'Téléphone'], ';');

            Payment::query()
                ->where('payer_id', $user->id)
                ->with('plan')
                ->latest('created_at')
                ->chunk(200, function ($lot) use ($sortie) {
                    foreach ($lot as $paiement) {
                        fputcsv($sortie, [
                            ($paiement->paid_at ?? $paiement->created_at)->format('d/m/Y H:i'),
                            $paiement->plan?->name() ?? 'Abonnement',
                            $paiement->amount_xaf,
                            match ($paiement->status) {
                                Payment::STATUS_SUCCESS => 'Réglé',
                                Payment::STATUS_PENDING => 'En attente',
                                Payment::STATUS_FAILED => 'Échec',
                                Payment::STATUS_CANCELLED => 'Annulé',
                                default => $paiement->status,
                            },
                            $paiement->internal_reference,
                            $paiement->phone,
                        ], ';');
                    }
                });

            fclose($sortie);
        }, $nom, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
