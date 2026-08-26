<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly QuotaService $quotas) {}

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

        // Les quatre chiffres de la maquette : ce qui est en cours, ce qui
        // est clos, ce qui attend une lecture, et ce que le client a laissé
        // derrière lui.
        $unreadMessages = Message::query()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->whereIn('conversation_id', Conversation::query()
                ->where('client_id', $user->id)
                ->select('id'))
            ->count();

        return view('dashboard.client', [
            'requests' => $requests,
            'counts' => $counts,
            'unreadMessages' => $unreadMessages,
            'reviewsLeft' => Review::query()->where('client_id', $user->id)->count(),
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

        // L'abonnement est toute la relation du prestataire avec SmartLink, et
        // le bandeau d'alerte ne paraît qu'à sept jours de l'échéance : d'ici
        // là, rien à l'écran ne dit quel palier il paie ni ce qu'il lui reste.
        $subscription = $user->activeSubscription();

        return view('dashboard.provider', [
            'requests' => $requests,
            'counts' => $counts,
            'servicesCount' => $servicesCount,
            'activeServicesCount' => $activeServicesCount,
            'pendingCount' => $pendingCount,
            'subscription' => $subscription,
            'plan' => $subscription?->plan,
            'remainingRequests' => $subscription ? $this->quotas->remainingRequests($user) : 0,
        ]);
    }
}
