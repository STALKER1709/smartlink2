<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModerationReport;
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
        ]);
    }
}
