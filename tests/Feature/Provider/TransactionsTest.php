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

    /**
     * L'export ne sort que les lignes du prestataire connecté : la même
     * garantie que la liste, sur un canal où rien ne se voit à l'écran.
     */
    public function test_export_contains_only_the_providers_own_payments(): void
    {
        $provider = User::factory()->provider()->create();
        $autre = User::factory()->provider()->create();

        $mien = Payment::factory()->successful()->create(['payer_id' => $provider->id]);
        $sien = Payment::factory()->successful()->create(['payer_id' => $autre->id]);

        $response = $this->actingAs($provider)->get(route('provider.transactions.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString($mien->internal_reference, $csv);
        $this->assertStringNotContainsString($sien->internal_reference, $csv);
    }

    public function test_client_cannot_export_transactions(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)->get(route('provider.transactions.export'))->assertForbidden();
    }
}
