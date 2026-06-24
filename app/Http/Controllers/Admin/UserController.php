<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->when($request->filled('role'), fn ($q) => $q->ofRole($request->string('role')))
            ->when($request->filled('term'), function ($q) use ($request) {
                $term = $request->string('term');
                $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $this->authorize('suspend', $user);

        $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();
        $this->auditLog->log($request->user(), 'user.suspended', $user);

        return back()->with('status', "Le compte de {$user->name} a été suspendu.");
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        $this->authorize('reactivate', $user);

        $user->forceFill(['status' => User::STATUS_ACTIVE])->save();
        $this->auditLog->log($request->user(), 'user.reactivated', $user);

        return back()->with('status', "Le compte de {$user->name} a été réactivé.");
    }
}
