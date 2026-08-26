<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // La recherche des maquettes : sur le nom de l'autre partie ou sur le
        // service dont on parle. `whereLike` sans sensibilité à la casse —
        // `like` est sensible à la casse sur PostgreSQL et ne l'est pas
        // ailleurs.
        $terme = trim((string) $request->query('q'));

        $conversations = Conversation::query()
            ->where(fn ($q) => $q
                ->where('client_id', $user->id)
                ->orWhere('provider_id', $user->id))
            ->when($terme !== '', fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('client', fn ($c) => $c->whereLike('name', '%'.$terme.'%', caseSensitive: false))
                ->orWhereHas('provider', fn ($c) => $c->whereLike('name', '%'.$terme.'%', caseSensitive: false))
                ->orWhereHas('provider.providerProfile', fn ($c) => $c->whereLike('business_name', '%'.$terme.'%', caseSensitive: false))
                ->orWhereHas('request.service', fn ($c) => $c->whereLike('title', '%'.$terme.'%', caseSensitive: false))))
            ->with(['client.clientProfile', 'provider.providerProfile', 'request.service', 'latestMessage.sender'])
            ->withCount(['messages as unread_count' => fn ($q) => $q
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_at')])
            ->orderByDesc(DB::raw('coalesce(last_message_at, created_at)'))
            ->paginate(10)
            ->withQueryString();

        return view('conversations.index', [
            'conversations' => $conversations,
            'terme' => $terme,
        ]);
    }

    public function show(Conversation $conversation): View
    {
        $this->authorize('view', $conversation);

        $conversation->load([
            'client.clientProfile', 'provider.providerProfile', 'request.service',
            'messages.sender',
        ]);

        $conversation->messages()
            ->where('sender_id', '!=', request()->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('conversations.show', [
            'conversation' => $conversation,
        ]);
    }
}
