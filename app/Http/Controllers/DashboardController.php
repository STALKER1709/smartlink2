<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isProvider()) {
            return $this->providerDashboard($user);
        }

        return $this->clientDashboard($user);
    }

    private function clientDashboard(User $user): View
    {
        $requests = $user->sentRequests()->with('service', 'provider.providerProfile')->latest()->take(5)->get();

        $counts = $user->sentRequests()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('dashboard.client', [
            'requests' => $requests,
            'counts' => $counts,
        ]);
    }

    private function providerDashboard(User $user): View
    {
        $requests = $user->receivedRequests()->with('service', 'client.clientProfile')->latest()->take(5)->get();

        $counts = $user->receivedRequests()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $servicesCount = $user->services()->count();
        $activeServicesCount = $user->services()->active()->count();
        $pendingCount = $user->receivedRequests()->whereIn('status', [
            ServiceRequest::STATUS_SENT, ServiceRequest::STATUS_VIEWED,
        ])->count();

        return view('dashboard.provider', [
            'requests' => $requests,
            'counts' => $counts,
            'servicesCount' => $servicesCount,
            'activeServicesCount' => $activeServicesCount,
            'pendingCount' => $pendingCount,
        ]);
    }
}
