<?php

namespace Tests\Feature;

use App\Models\Dispute;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisputeTest extends TestCase
{
    use RefreshDatabase;

    private function demande(User $client, User $provider, string $status = ServiceRequest::STATUS_COMPLETED): ServiceRequest
    {
        $service = Service::factory()->create([
            'provider_id' => $provider->id,
            'category_id' => ServiceCategory::factory()->create()->id,
        ]);

        return ServiceRequest::factory()->create([
            'client_id' => $client->id,
            'provider_id' => $provider->id,
            'service_id' => $service->id,
            'status' => $status,
        ]);
    }

    private function valide(): array
    {
        return [
            'reason' => 'retard_important',
            'description' => "Le prestataire n'est pas venu au rendez-vous convenu et ne répond plus depuis trois jours.",
        ];
    }

    public function test_a_party_can_report_a_problem_on_an_engaged_request(): void
    {
        $client = User::factory()->client()->create();
        $demande = $this->demande($client, User::factory()->provider()->create());

        $this->actingAs($client)
            ->post(route('disputes.store', $demande), $this->valide())
            ->assertRedirect(route('requests.show', $demande));

        $this->assertDatabaseHas('disputes', [
            'request_id' => $demande->id,
            'reporter_id' => $client->id,
            'status' => Dispute::STATUS_OPEN,
        ]);
    }

    /**
     * Un litige avant l'acceptation n'a rien à trancher : il n'y a pas encore
     * de prestation.
     */
    public function test_a_request_not_yet_engaged_cannot_be_reported(): void
    {
        $client = User::factory()->client()->create();
        $demande = $this->demande($client, User::factory()->provider()->create(), ServiceRequest::STATUS_SENT);

        $this->actingAs($client)->get(route('disputes.create', $demande))->assertForbidden();
    }

    public function test_a_stranger_cannot_report_someone_elses_request(): void
    {
        $demande = $this->demande(User::factory()->client()->create(), User::factory()->provider()->create());

        $this->actingAs(User::factory()->client()->create())
            ->post(route('disputes.store', $demande), $this->valide())
            ->assertForbidden();
    }

    /**
     * Un second signalement ouvert sur la même demande ne ferait que dédoubler
     * le travail de l'équipe.
     */
    public function test_a_second_open_report_is_refused(): void
    {
        $client = User::factory()->client()->create();
        $demande = $this->demande($client, User::factory()->provider()->create());

        $this->actingAs($client)->post(route('disputes.store', $demande), $this->valide());
        $this->actingAs($client)->post(route('disputes.store', $demande), $this->valide())->assertForbidden();

        $this->assertDatabaseCount('disputes', 1);
    }

    public function test_a_one_line_description_is_refused(): void
    {
        $client = User::factory()->client()->create();
        $demande = $this->demande($client, User::factory()->provider()->create());

        $this->actingAs($client)
            ->post(route('disputes.store', $demande), ['reason' => 'autre', 'description' => 'Bof'])
            ->assertSessionHasErrors('description');
    }

    /**
     * La décision doit atteindre la base, pas seulement le message de
     * confirmation : `update()` écartait ces colonnes en silence, absentes de
     * `Fillable`, et l'écran annonçait « clos » sur une ligne restée ouverte.
     */
    public function test_an_admin_decision_is_actually_recorded(): void
    {
        $client = User::factory()->client()->create();
        $demande = $this->demande($client, User::factory()->provider()->create());
        $this->actingAs($client)->post(route('disputes.store', $demande), $this->valide());

        $dispute = Dispute::firstOrFail();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.disputes.resolve', $dispute), [
            'status' => Dispute::STATUS_RESOLVED,
            'resolution' => 'Le prestataire rembourse le déplacement.',
        ]);

        $dispute->refresh();

        $this->assertSame(Dispute::STATUS_RESOLVED, $dispute->status);
        $this->assertSame('Le prestataire rembourse le déplacement.', $dispute->resolution);
        $this->assertSame($admin->id, $dispute->reviewed_by);
        $this->assertNotNull($dispute->reviewed_at);
    }

    public function test_a_decision_requires_a_written_reason(): void
    {
        $client = User::factory()->client()->create();
        $demande = $this->demande($client, User::factory()->provider()->create());
        $this->actingAs($client)->post(route('disputes.store', $demande), $this->valide());

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.disputes.resolve', Dispute::firstOrFail()), ['status' => Dispute::STATUS_RESOLVED])
            ->assertSessionHasErrors('resolution');
    }

    public function test_a_client_cannot_reach_the_admin_queue(): void
    {
        $this->actingAs(User::factory()->client()->create())
            ->get(route('admin.disputes.index'))
            ->assertForbidden();
    }
}
