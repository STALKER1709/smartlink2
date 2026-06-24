<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => User::factory()->client(),
            'provider_id' => User::factory()->provider(),
            'service_id' => null,
            'message' => fake()->paragraph(),
            'preferred_date' => fake()->optional()->dateTimeBetween('now', '+1 month'),
            'status' => ServiceRequest::STATUS_SENT,
        ];
    }

    public function forService(Service $service): static
    {
        return $this->state([
            'service_id' => $service->id,
            'provider_id' => $service->provider_id,
        ]);
    }

    public function accepted(): static
    {
        return $this->state([
            'status' => ServiceRequest::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => ServiceRequest::STATUS_COMPLETED,
            'responded_at' => now()->subDays(2),
            'completed_at' => now(),
        ]);
    }

    public function refused(): static
    {
        return $this->state([
            'status' => ServiceRequest::STATUS_REFUSED,
            'responded_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => ServiceRequest::STATUS_CANCELLED,
            'cancellation_reason' => fake()->sentence(),
        ]);
    }
}
