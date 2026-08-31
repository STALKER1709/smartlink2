<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\ModerationReport;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'clients' => User::query()->ofRole(User::ROLE_CLIENT)->count(),
            'providers' => User::query()->ofRole(User::ROLE_PROVIDER)->count(),
            'suspended_users' => User::query()->where('status', User::STATUS_SUSPENDED)->count(),
            'services_active' => Service::query()->active()->count(),
            'services_total' => Service::query()->count(),
            'requests_total' => ServiceRequest::query()->count(),
            'moderation_pending' => ModerationReport::query()->pending()->count(),
        ];

        $requestsByStatus = ServiceRequest::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $recentUsers = User::query()->latest()->take(5)->get();

        /*
         * Les trois files d'attente de la maquette, avec leurs premières
         * entrées. Elles étaient jusqu'ici une rangée de pastilles portant
         * chacune un compteur — et deux de ces compteurs étaient comptés
         * depuis la vue, requête comprise. Un écran de supervision doit dire
         * *qui* attend, pas seulement combien.
         */
        $verificationsEnAttente = ProviderProfile::query()
            ->whereNotNull('id_card_path')
            ->where('id_card_verified', false);

        $signalementsEnAttente = ModerationReport::query()->pending();

        $litigesOuverts = Dispute::query()->open();

        /*
         * Les entrées sont réduites à un libellé et une date : la vue n'a pas
         * à connaître trois modèles pour afficher trois listes de la même
         * forme, et le libellé de chacun se décide là où il est produit.
         */
        $files = [
            'verifications' => [
                'total' => (clone $verificationsEnAttente)->count(),
                'entrees' => (clone $verificationsEnAttente)->latest()->take(3)->get()
                    ->map(fn (ProviderProfile $profil) => [
                        'libelle' => $profil->business_name,
                        'date' => $profil->created_at,
                    ]),
            ],
            'moderation' => [
                'total' => (clone $signalementsEnAttente)->count(),
                'entrees' => (clone $signalementsEnAttente)->with('moderatable')->latest()->take(3)->get()
                    ->map(fn (ModerationReport $rapport) => [
                        'libelle' => $rapport->moderatable?->title
                            ?? $rapport->moderatable?->business_name
                            ?? __('Contenu supprimé'),
                        'date' => $rapport->created_at,
                    ]),
            ],
            'litiges' => [
                'total' => (clone $litigesOuverts)->count(),
                'entrees' => (clone $litigesOuverts)->with('request.service')->latest()->take(3)->get()
                    ->map(fn (Dispute $litige) => [
                        'libelle' => $litige->request?->service?->title ?? __('Demande directe'),
                        'date' => $litige->created_at,
                    ]),
            ],
        ];

        /*
         * Les maquettes posent une tendance sous les deux compteurs de
         * population. Elle compare les inscriptions du mois en cours à celles
         * du mois précédent, et se tait quand le mois précédent est vide :
         * passer de zéro à trois n'est pas « +300 % ».
         */
        $inscrits = fn (string $role, $depuis, $jusqua = null) => User::query()
            ->ofRole($role)
            ->where('created_at', '>=', $depuis)
            ->when($jusqua !== null, fn ($q) => $q->where('created_at', '<', $jusqua))
            ->count();

        $moisCourant = now()->startOfMonth();
        $moisPrecedent = now()->subMonthNoOverflow()->startOfMonth();

        $tendances = [];
        foreach ([User::ROLE_CLIENT => 'clients', User::ROLE_PROVIDER => 'providers'] as $role => $cle) {
            $avant = $inscrits($role, $moisPrecedent, $moisCourant);
            $tendances[$cle] = $avant > 0
                ? (int) round(($inscrits($role, $moisCourant) - $avant) / $avant * 100)
                : null;
        }

        return view('admin.dashboard', [
            'stats' => $stats,
            'requestsByStatus' => $requestsByStatus,
            'recentUsers' => $recentUsers,
            'tendances' => $tendances,
            'files' => $files,
        ]);
    }
}
