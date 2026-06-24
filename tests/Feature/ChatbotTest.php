<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_ask_the_chatbot(): void
    {
        $response = $this->postJson(route('chatbot.ask'), [
            'message' => 'Bonjour',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['reply']);
    }

    public function test_message_is_required(): void
    {
        $response = $this->postJson(route('chatbot.ask'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('message');
    }

    public function test_chatbot_never_mentions_online_payment_processing(): void
    {
        $response = $this->postJson(route('chatbot.ask'), [
            'message' => 'Quel est le prix ?',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('hors plateforme', $response->json('reply'));
    }
}
