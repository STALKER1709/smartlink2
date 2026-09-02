<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\RequestStatusChangedNotification;
use App\Support\RequestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientScreensTest extends TestCase
{
    use RefreshDatabase;

    private function demande(User $client, string $status): ServiceRequest
    {
        $provider = User::factory()->provider()->create();
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

    /**
     * Les neuf pastilles s'affichaient toutes, y compris celles qui
     * n'auraient rien renvoyé : un filtre qui rend zéro est un piège.
     */
    public function test_request_filters_only_offer_statuses_that_exist(): void
    {
        $client = User::factory()->client()->create();
        $this->demande($client, ServiceRequest::STATUS_SENT);
        $this->demande($client, ServiceRequest::STATUS_COMPLETED);

        $response = $this->actingAs($client)->get(route('requests.index'));

        $response->assertOk();
        $response->assertSee(RequestStatus::label(ServiceRequest::STATUS_SENT));
        $response->assertSee(RequestStatus::label(ServiceRequest::STATUS_COMPLETED));
        $response->assertDontSee(RequestStatus::label(ServiceRequest::STATUS_REFUSED));
        $response->assertDontSee(RequestStatus::label(ServiceRequest::STATUS_CANCELLED));
    }

    /**
     * Une notification de changement de statut écrivait la valeur de la
     * colonne : « Le statut de votre demande #25 est passé à "in_progress". »
     */
    public function test_status_notification_never_shows_a_raw_status(): void
    {
        $client = User::factory()->client()->create();
        $demande = $this->demande($client, ServiceRequest::STATUS_IN_PROGRESS);

        $client->notify(new RequestStatusChangedNotification(
            $demande,
            ServiceRequest::STATUS_ACCEPTED,
            ServiceRequest::STATUS_IN_PROGRESS,
        ));

        $response = $this->actingAs($client)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertDontSee(ServiceRequest::STATUS_IN_PROGRESS);
        $response->assertSee($demande->service->title, false);
        $response->assertSee(mb_strtolower(RequestStatus::label(ServiceRequest::STATUS_IN_PROGRESS)));
    }

    /**
     * Sans service ni prestataire, le formulaire ne peut pas aboutir : la
     * validation exige l'un des deux. Il était tout de même offert, et
     * l'envoi renvoyait « Le champ service est obligatoire lorsque provider
     * id n'est pas présent ».
     */
    public function test_request_form_without_a_recipient_offers_a_choice_instead(): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('requests.create'));

        $response->assertOk();
        $response->assertSee('À qui adressez-vous cette demande ?', false);
        $response->assertDontSee('Envoyer la demande');
    }
}
