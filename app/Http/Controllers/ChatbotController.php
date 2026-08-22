<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChatbotMessageRequest;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;

class ChatbotController extends Controller
{
    public function __construct(private readonly ChatbotService $chatbot) {}

    public function ask(StoreChatbotMessageRequest $request): JsonResponse
    {
        $reply = $this->chatbot->ask(
            $request->string('message')->toString(),
            $request->input('history', []),
            $request->user(),
        );

        return response()->json(['reply' => $reply]);
    }
}
