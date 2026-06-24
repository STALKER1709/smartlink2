<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(Request $request): View
    {
        $services = Service::query()
            ->with(['provider', 'category'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('term'), fn ($q) => $q->searchTerm($request->string('term')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.services.index', [
            'services' => $services,
        ]);
    }

    public function toggleStatus(Request $request, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        $newStatus = $service->status === Service::STATUS_ACTIVE
            ? Service::STATUS_INACTIVE
            : Service::STATUS_ACTIVE;

        $service->update(['status' => $newStatus]);
        $this->auditLog->log($request->user(), 'service.status_toggled', $service, ['status' => $newStatus]);

        return back()->with('status', 'Statut du service mis à jour.');
    }

    public function destroy(Request $request, Service $service): RedirectResponse
    {
        $this->authorize('delete', $service);

        $service->delete();
        $this->auditLog->log($request->user(), 'service.deleted', $service);

        return back()->with('status', 'Service supprimé par un administrateur.');
    }
}
