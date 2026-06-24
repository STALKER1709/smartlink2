<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestActionRequest;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\RequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequestController extends Controller
{
    public function __construct(private readonly RequestService $requests)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $query = $user->isProvider() ? $user->receivedRequests() : $user->sentRequests();

        $serviceRequests = $query
            ->with(['service', 'client.clientProfile', 'provider.providerProfile'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('requests.index', [
            'requests' => $serviceRequests,
        ]);
    }

    public function show(Request $request, ServiceRequest $serviceRequest): View
    {
        $this->authorize('view', $serviceRequest);

        $user = $request->user();

        if ($user->isProvider() && $user->id === $serviceRequest->provider_id) {
            $this->requests->markViewed($serviceRequest, $user);
        }

        $serviceRequest->load([
            'service', 'client.clientProfile', 'provider.providerProfile',
            'statusHistory.changedBy', 'conversation.messages.sender', 'review',
        ]);

        return view('requests.show', [
            'serviceRequest' => $serviceRequest,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ServiceRequest::class);

        $service = null;
        if ($request->filled('service_id')) {
            $service = Service::query()->active()->findOrFail($request->integer('service_id'));
        }

        $provider = null;
        if (! $service && $request->filled('provider_id')) {
            $provider = User::query()->ofRole(User::ROLE_PROVIDER)
                ->with('providerProfile')
                ->findOrFail($request->integer('provider_id'));
        }

        return view('requests.create', [
            'service' => $service,
            'provider' => $provider,
        ]);
    }

    public function store(StoreServiceRequestRequest $request): RedirectResponse
    {
        $this->authorize('create', ServiceRequest::class);

        $data = $request->validated();
        $providerId = $data['provider_id'] ?? null;

        if (! empty($data['service_id'])) {
            $service = Service::findOrFail($data['service_id']);
            $providerId = $service->provider_id;
        }

        $serviceRequest = $this->requests->create($request->user(), [
            'provider_id' => $providerId,
            'service_id' => $data['service_id'] ?? null,
            'message' => $data['message'],
            'preferred_date' => $data['preferred_date'] ?? null,
            'as_draft' => $data['action'] === 'draft',
        ]);

        return redirect()->route('requests.show', $serviceRequest)
            ->with('status', $data['action'] === 'draft'
                ? 'Votre demande a été enregistrée comme brouillon.'
                : 'Votre demande a été envoyée au prestataire.');
    }

    public function accept(ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('respond', $serviceRequest);

        $this->requests->accept($serviceRequest, request()->user());

        return back()->with('status', 'Demande acceptée. Une conversation a été ouverte avec le client.');
    }

    public function refuse(RequestActionRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('respond', $serviceRequest);

        $this->requests->refuse($serviceRequest, $request->user(), $request->validated('reason'));

        return back()->with('status', 'Demande refusée.');
    }

    public function start(ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('respond', $serviceRequest);

        $this->requests->start($serviceRequest, request()->user());

        return back()->with('status', 'La prestation est maintenant en cours.');
    }

    public function complete(ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('respond', $serviceRequest);

        $this->requests->complete($serviceRequest, request()->user());

        return back()->with('status', 'Demande marquée comme terminée.');
    }

    public function cancel(RequestActionRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('cancel', $serviceRequest);

        $this->requests->cancel($serviceRequest, $request->user(), $request->validated('reason'));

        return back()->with('status', 'Demande annulée.');
    }
}
