<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\AuditLogService;
use App\Services\QuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Les paliers se modifient, ils ne se créent ni ne se suppriment : leur code
 * est un ensemble fermé sur lequel s'appuient le reste du code et les
 * fichiers de langue. Désactiver un palier le retire de la vente sans
 * toucher aux abonnements en cours.
 */
class PlanController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly QuotaService $quotas,
    ) {}

    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => Plan::query()
                ->orderBy('sort_order')
                ->withCount(['subscriptions as active_subscriptions_count' => fn ($q) => $q->usable()])
                ->get(),
        ]);
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.edit', ['plan' => $plan]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        $before = $plan->only([
            'price_xaf', 'max_services', 'max_monthly_requests',
            'is_featured', 'has_ai_writing', 'has_stats', 'is_active',
        ]);

        $plan->update($request->validated());

        $this->auditLog->log($request->user(), 'plan.updated', $plan, [
            'before' => $before,
            'after' => $plan->only(array_keys($before)),
        ]);

        // Les limites viennent de changer : la visibilité des prestataires
        // qui portent ce palier doit être réévaluée, sans quoi un plafond
        // relevé ne les ferait pas réapparaître avant le passage de nuit.
        $this->refreshAffectedProviders($plan);

        return redirect()
            ->route('admin.plans.index')
            ->with('status', __('ui.admin_plans.updated', ['plan' => $plan->name()]));
    }

    private function refreshAffectedProviders(Plan $plan): void
    {
        Subscription::query()
            ->where('plan_id', $plan->id)
            ->usable()
            ->with('user.providerProfile')
            ->chunkById(200, function ($subscriptions) {
                foreach ($subscriptions as $subscription) {
                    if ($subscription->user !== null) {
                        $this->quotas->refreshListing($subscription->user);
                    }
                }
            });
    }
}
