<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;

class MessageController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function store(StoreMessageRequest $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('update', $conversation);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        $conversation->update(['last_message_at' => now()]);

        $this->notifications->notifyNewMessage($message);

        return back();
    }
}
