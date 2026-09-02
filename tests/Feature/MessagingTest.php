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

    /**
     * La recherche de la messagerie ignore la casse.
     *
     * `like` la respecte sur PostgreSQL et l'ignore ailleurs : une recherche
     * en minuscules rendait une liste vide en production, sans erreur nulle
     * part. `whereLike(caseSensitive: false)` compile en `ilike`, et ce test
     * ne prouve quelque chose que sur le second passage.
     */
    public function test_the_conversation_search_ignores_case(): void
    {
        $client = User::factory()->client()->create();
        $prestataire = User::factory()->provider()->create(['name' => 'Jean-Paul Plomberie']);

        $conversation = Conversation::factory()->create([
            'client_id' => $client->id,
            'provider_id' => $prestataire->id,
        ]);

        $this->actingAs($client)
            ->get(route('conversations.index', ['q' => 'jean-paul']))
            ->assertOk()
            ->assertViewHas('conversations', fn ($conversations) => $conversations->pluck('id')->contains($conversation->id));
    }

    /**
     * La conversation ouverte porte la colonne des fils, qui est ce qui
     * permet de passer de l'une à l'autre sans repasser par la liste.
     */
    public function test_an_open_conversation_carries_the_thread_column(): void
    {
        $client = User::factory()->client()->create();

        $ouverte = Conversation::factory()->create(['client_id' => $client->id]);
        $autre = Conversation::factory()->create(['client_id' => $client->id]);

        $this->actingAs($client)
            ->get(route('conversations.show', $ouverte))
            ->assertOk()
            ->assertViewHas('fils', fn ($fils) => $fils->pluck('id')->contains($ouverte->id)
                && $fils->pluck('id')->contains($autre->id));
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
