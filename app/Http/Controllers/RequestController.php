<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestActionRequest;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\QuotaService;
use App\Services\RequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequestController extends Controller
{
    public function __construct(
        private readonly RequestService $requests,
        private readonly QuotaService $quotas,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $query = $user->isProvider() ? $user->receivedRequests() : $user->sentRequests();

        $serviceRequests = $query
            /*
             * Les deux métiers affichés sur chaque ligne — celui du service et
             * celui du prestataire — se lisaient à travers une relation non
             * préchargée : une requête par ligne, soit dix allers-retours de
             * plus par page, invisibles sur SQLite et payés à chaque fois sur
             * une base distante.
             */
            ->with([
                'service.category',
                'client.clientProfile',
                'provider.providerProfile.category',
            ])
            // Le point rouge des maquettes : une demande dont la conversation
            // porte un message qu'on n'a pas lu.
            ->withCount(['conversation as unread_count' => fn ($q) => $q
                ->join('messages', 'messages.conversation_id', '=', 'conversations.id')
                ->where('messages.sender_id', '!=', $user->id)
                ->whereNull('messages.read_at')])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Neuf pastilles de filtre s'affichaient toujours, y compris celles
        // qui n'auraient rien renvoyé. Seuls les statuts réellement présents
        // sont proposés, avec leur nombre : un filtre qui rend zéro n'est pas
        // un filtre, c'est un piège.
        //
        // Le décompte repart d'une relation neuve, et `reorder()` retire tout
        // tri : `paginate()` avait posé « order by created_at » et « limit »
        // sur le constructeur partagé, que le regroupement traînait ensuite.
        // SQLite l'accepte, PostgreSQL refuse — « column requests.created_at
        // must appear in the GROUP BY clause » — et la page tombait en erreur
        // sur le moteur de la production seulement.
        $comptes = ($user->isProvider() ? $user->receivedRequests() : $user->sentRequests())
            ->reorder()
            ->getQuery()
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return view('requests.index', [
            'requests' => $serviceRequests,
            'comptes' => $comptes,
        ]);
    }

    public function show(Request $request, ServiceRequest $serviceRequest): View
    {
        $this->authorize('view', $serviceRequest);

        $user = $request->user();

        // Le plafond mensuel se consomme à la première lecture d'une demande.
        // Une demande déjà ouverte reste lisible sans rien décompter : ce qui
        // est engagé le reste, quel que soit l'état de l'abonnement.
        if ($user->isProvider()
            && $user->id === $serviceRequest->provider_id
            && $serviceRequest->status === ServiceRequest::STATUS_SENT) {
            // La réservation vaut vérification : elle n'aboutit que s'il
            // restait une place, et c'est la base qui tranche. Vérifier puis
            // consommer en deux temps laissait deux lectures simultanées
            // passer toutes les deux.
            if (! $this->quotas->consumeRequestRead($user)) {
                return view('requests.locked', [
                    'serviceRequest' => $serviceRequest,
                    'plan' => $this->quotas->plan($user),
                ]);
            }

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
