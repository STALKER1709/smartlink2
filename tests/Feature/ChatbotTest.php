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

    public function test_chatbot_states_smartlink_takes_no_cut_of_the_service(): void
    {
        $response = $this->postJson(route('chatbot.ask'), [
            'message' => 'Quel est le prix ?',
        ]);

        $response->assertOk();

        $reply = $response->json('reply');
        $this->assertStringContainsString('hors plateforme', $reply);
        $this->assertStringContainsString('abonnement', $reply);
    }

    public function test_chatbot_can_describe_the_provider_subscription(): void
    {
        $response = $this->postJson(route('chatbot.ask'), [
            'message' => 'Quels sont vos paliers d\'abonnement ?',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('essai gratuit', $response->json('reply'));
    }
}
