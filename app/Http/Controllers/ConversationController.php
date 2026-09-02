<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $terme = trim((string) $request->query('q'));

        return view('conversations.index', [
            'conversations' => $this->fils($request->user(), $terme)->paginate(10)->withQueryString(),
            'terme' => $terme,
        ]);
    }

    /**
     * Les fils de l'utilisateur, du plus récemment actif au plus ancien.
     *
     * La recherche des maquettes porte sur le nom de l'autre partie ou sur le
     * service dont on parle. `whereLike` sans sensibilité à la casse — `like`
     * est sensible à la casse sur PostgreSQL et ne l'est pas ailleurs.
     *
     * @return Builder<Conversation>
     */
    private function fils(User $user, string $terme = ''): Builder
    {
        return Conversation::query()
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
            ->orderByDesc(DB::raw('coalesce(last_message_at, created_at)'));
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

        /*
         * La colonne des fils accompagne la conversation ouverte à partir de
         * `lg`, comme dans la maquette : sans elle, passer d'un fil à l'autre
         * demandait un aller-retour par la liste.
         *
         * Vingt fils au plus, et sans pagination : une colonne latérale qui
         * pagine oblige à choisir entre changer de page et garder la
         * conversation ouverte. Au-delà, la liste complète reste à un clic.
         */
        $fils = $this->fils(request()->user())->take(20)->get();

        return view('conversations.show', [
            'conversation' => $conversation,
            'fils' => $fils,
        ]);
    }
}
