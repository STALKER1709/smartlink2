<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceImage;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->ofRole(User::ROLE_PROVIDER)->with('providerProfile')->get()
            ->each(function (User $provider): void {
                Service::factory(fake()->numberBetween(2, 5))
                    ->create([
                        'provider_id' => $provider->id,
                        'category_id' => $provider->providerProfile?->category_id,
                        'city' => $provider->providerProfile?->city,
                    ])
                    ->each(function (Service $service): void {
                        $imageCount = fake()->boolean(70) ? fake()->numberBetween(1, 3) : 0;

                        for ($position = 0; $position < $imageCount; $position++) {
                            ServiceImage::factory()->create([
                                'service_id' => $service->id,
                                'position' => $position,
                            ]);
                        }
                    });
            });
    }
}
