<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(15);

        // Les notifications écrites avant que la charge utile ne porte le
        // titre du service ne peuvent que dire « Votre demande » : huit
        // rangées identiques, sans moyen de les distinguer. Le titre se
        // retrouve par l'identifiant de la demande, en une requête pour la
        // page entière.
        $titres = ServiceRequest::query()
            ->whereIn('id', collect($notifications->items())
                ->pluck('data.request_id')
                ->filter()
                ->unique())
            ->with('service:id,title')
            ->get()
            ->mapWithKeys(fn (ServiceRequest $r) => [$r->id => $r->service?->title]);

        return view('notifications.index', [
            'notifications' => $notifications,
            'nonLues' => $request->user()->unreadNotifications()->count(),
            'titres' => $titres,
        ]);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->where('id', $notification)->firstOrFail();
        $record->markAsRead();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
