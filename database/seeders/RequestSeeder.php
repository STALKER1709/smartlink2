<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\RequestService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class RequestSeeder extends Seeder
{
    private Collection $providers;

    public function run(RequestService $requests): void
    {
        $clients = User::query()->ofRole(User::ROLE_CLIENT)->get();
        $this->providers = User::query()->ofRole(User::ROLE_PROVIDER)->with('services')->get();

        if ($clients->isEmpty() || $this->providers->isEmpty()) {
            return;
        }

        $this->createDrafts($requests, $clients, 4);
        $this->createSent($requests, $clients, 5);
        $this->createViewed($requests, $clients, 4);
        $this->createAccepted($requests, $clients, 6);
        $this->createInProgress($requests, $clients, 4);
        $this->createCompleted($requests, $clients, 8);
        $this->createRefused($requests, $clients, 3);
        $this->createCancelled($requests, $clients, 3);
    }

    private function createDrafts(RequestService $requests, Collection $clients, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $client = $clients->random();
            $provider = $this->providers->random();

            $requests->create($client, [
                'provider_id' => $provider->id,
                'service_id' => $this->pickService($provider),
                'message' => fake()->paragraph(),
                'preferred_date' => fake()->boolean(50) ? fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d') : null,
                'as_draft' => true,
            ]);
        }
    }

    private function createSent(RequestService $requests, Collection $clients, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->newSentRequest($requests, $clients);
        }
    }

    private function createViewed(RequestService $requests, Collection $clients, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $request = $this->newSentRequest($requests, $clients);
            $requests->markViewed($request, $request->provider);
        }
    }

    private function createAccepted(RequestService $requests, Collection $clients, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $request = $this->newSentRequest($requests, $clients);
            $requests->accept($request, $request->provider);

            $conversation = $request->conversation()->first();

            if ($conversation) {
                Message::factory()->create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $request->client_id,
                    'body' => fake()->sentence(fake()->numberBetween(6, 15)),
                ]);
                Message::factory()->create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $request->provider_id,
                    'body' => fake()->sentence(fake()->numberBetween(6, 15)),
                ]);
                $conversation->forceFill(['last_message_at' => now()])->save();
            }
        }
    }

    private function createInProgress(RequestService $requests, Collection $clients, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $request = $this->newSentRequest($requests, $clients);
            $requests->accept($request, $request->provider);
            $requests->start($request, $request->provider);
        }
    }

    private function createCompleted(RequestService $requests, Collection $clients, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $request = $this->newSentRequest($requests, $clients);
            $requests->accept($request, $request->provider);
            $requests->start($request, $request->provider);
            $requests->complete($request, $request->provider);

            if (fake()->boolean(75)) {
                $this->leaveReview($request);
            }
        }
    }

    private function createRefused(RequestService $requests, Collection $clients, int $count): void
    {
        $reasons = [
            'Je ne suis pas disponible sur la période demandée.',
            "Cette prestation ne correspond pas à mon domaine d'intervention.",
            "Zone géographique trop éloignée de mon secteur d'activité.",
        ];

        for ($i = 0; $i < $count; $i++) {
            $request = $this->newSentRequest($requests, $clients);
            $requests->refuse($request, $request->provider, fake()->randomElement($reasons));
        }
    }

    private function createCancelled(RequestService $requests, Collection $clients, int $count): void
    {
        $reasons = [
            "J'ai trouvé un autre prestataire.",
            "Je n'ai finalement plus besoin de cette prestation.",
            'Le délai ne me convient plus.',
        ];

        for ($i = 0; $i < $count; $i++) {
            $request = $this->newSentRequest($requests, $clients);
            $requests->cancel($request, $request->client, fake()->randomElement($reasons));
        }
    }

    private function newSentRequest(RequestService $requests, Collection $clients): ServiceRequest
    {
        $client = $clients->random();
        $provider = $this->providers->random();

        return $requests->create($client, [
            'provider_id' => $provider->id,
            'service_id' => $this->pickService($provider),
            'message' => fake()->paragraph(),
            'preferred_date' => fake()->boolean(50) ? fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d') : null,
        ]);
    }

    private function pickService(User $provider): ?int
    {
        if ($provider->services->isEmpty() || ! fake()->boolean(70)) {
            return null;
        }

        return $provider->services->random()->id;
    }

    private function leaveReview(ServiceRequest $request): void
    {
        Review::create([
            'request_id' => $request->id,
            'client_id' => $request->client_id,
            'provider_id' => $request->provider_id,
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake()->boolean(80) ? fake()->sentence(fake()->numberBetween(8, 20)) : null,
        ]);

        $provider = $request->provider;
        $stats = $provider->reviewsReceived()->visible();

        $provider->providerProfile?->forceFill([
            'rating_avg' => round($stats->avg('rating') ?? 0, 2),
            'rating_count' => $stats->count(),
        ])->save();
    }
}
