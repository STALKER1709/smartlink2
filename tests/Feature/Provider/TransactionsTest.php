<?php

namespace Tests\Feature\Provider;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('provider.transactions.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_client_cannot_access_provider_transactions(): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('provider.transactions.index'));

        $response->assertForbidden();
    }

    public function test_provider_sees_only_their_own_payments(): void
    {
        $provider = User::factory()->provider()->create();
        $otherProvider = User::factory()->provider()->create();

        $mine = Payment::factory()->successful()->create(['payer_id' => $provider->id]);
        Payment::factory()->successful()->create(['payer_id' => $otherProvider->id]);

        $response = $this->actingAs($provider)->get(route('provider.transactions.index'));

        $response->assertOk();
        $response->assertViewIs('provider.transactions.index');
        $response->assertSee($mine->internal_reference);
    }
}
