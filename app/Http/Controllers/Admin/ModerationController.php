<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModerationReport;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModerationController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function index(): View
    {
        $reports = ModerationReport::query()
            ->pending()
            ->with('moderatable')
            ->latest()
            ->paginate(20);

        return view('admin.moderation.index', [
            'reports' => $reports,
        ]);
    }

    /**
     * Classe le signalement sans toucher au contenu. Supprimer un service ou
     * suspendre un compte reste une action distincte, décidée et tracée à part.
     */
    public function dismiss(Request $request, ModerationReport $report): RedirectResponse
    {
        $report->update([
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->auditLog->log($request->user(), 'moderation.dismissed', $report, [
            'moderatable_type' => $report->moderatable_type,
            'moderatable_id' => $report->moderatable_id,
        ]);

        return back()->with('status', __('ui.moderation.dismissed'));
    }
}
