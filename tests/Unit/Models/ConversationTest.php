<?php

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_other_participant_returns_provider_for_client(): void
    {
        $client = User::factory()->client()->create();
        $provider = User::factory()->provider()->create();
        $conversation = Conversation::factory()->create([
            'client_id' => $client->id,
            'provider_id' => $provider->id,
        ]);

        $this->assertTrue($provider->is($conversation->otherParticipant($client)));
    }

    public function test_other_participant_returns_client_for_provider(): void
    {
        $client = User::factory()->client()->create();
        $provider = User::factory()->provider()->create();
        $conversation = Conversation::factory()->create([
            'client_id' => $client->id,
            'provider_id' => $provider->id,
        ]);

        $this->assertTrue($client->is($conversation->otherParticipant($provider)));
    }
}
