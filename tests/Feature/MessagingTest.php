<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_view_their_conversations_index(): void
    {
        $client = User::factory()->client()->create();
        $conversation = Conversation::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client)->get(route('conversations.index'));

        $response->assertOk();
        $response->assertViewHas('conversations', fn ($conversations) => $conversations->pluck('id')->contains($conversation->id));
    }

    public function test_strangers_cannot_view_someone_elses_conversation(): void
    {
        $conversation = Conversation::factory()->create();
        $stranger = User::factory()->client()->create();

        $response = $this->actingAs($stranger)->get(route('conversations.show', $conversation));

        $response->assertForbidden();
    }

    public function test_viewing_a_conversation_marks_the_other_partys_messages_as_read(): void
    {
        $client = User::factory()->client()->create();
        $provider = User::factory()->provider()->create();
        $conversation = Conversation::factory()->create([
            'client_id' => $client->id,
            'provider_id' => $provider->id,
        ]);
        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $provider->id,
        ]);

        $this->actingAs($client)->get(route('conversations.show', $conversation));

        $this->assertNotNull($message->refresh()->read_at);
    }

    public function test_participant_can_send_a_message(): void
    {
        $client = User::factory()->client()->create();
        $conversation = Conversation::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client)->post(route('conversations.messages.store', $conversation), [
            'body' => 'Bonjour, je confirme le rendez-vous.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $client->id,
            'body' => 'Bonjour, je confirme le rendez-vous.',
        ]);
    }

    public function test_non_participant_cannot_send_a_message(): void
    {
        $conversation = Conversation::factory()->create();
        $stranger = User::factory()->client()->create();

        $response = $this->actingAs($stranger)->post(route('conversations.messages.store', $conversation), [
            'body' => 'Je m\'invite dans la discussion.',
        ]);

        $response->assertForbidden();
    }

    public function test_message_body_is_required(): void
    {
        $client = User::factory()->client()->create();
        $conversation = Conversation::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client)->post(route('conversations.messages.store', $conversation), [
            'body' => '',
        ]);

        $response->assertSessionHasErrors('body');
    }
}
