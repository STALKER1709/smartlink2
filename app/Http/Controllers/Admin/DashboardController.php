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

        return view('admin.dashboard', [
            'stats' => $stats,
            'requestsByStatus' => $requestsByStatus,
            'recentUsers' => $recentUsers,
        ]);
    }
}
