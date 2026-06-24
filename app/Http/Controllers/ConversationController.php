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

        $conversations = Conversation::query()
            ->where('client_id', $user->id)
            ->orWhere('provider_id', $user->id)
            ->with(['client.clientProfile', 'provider.providerProfile', 'request.service'])
            ->orderByDesc(DB::raw('coalesce(last_message_at, created_at)'))
            ->paginate(10)
            ->withQueryString();

        return view('conversations.index', [
            'conversations' => $conversations,
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
