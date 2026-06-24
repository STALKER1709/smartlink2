<?php

namespace Database\Seeders;

use App\Models\ClientProfile;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $categories = ServiceCategory::all();

        $demoClient = User::factory()->client()->create([
            'name' => 'Aïcha Mballa',
            'email' => 'client@smartlink.cm',
        ]);
        ClientProfile::factory()->create([
            'user_id' => $demoClient->id,
            'first_name' => 'Aïcha',
            'last_name' => 'Mballa',
            'city' => 'Douala',
        ]);

        $demoProvider = User::factory()->provider()->create([
            'name' => "Jean-Paul Eto'o",
            'email' => 'provider@smartlink.cm',
        ]);
        ProviderProfile::factory()->create([
            'user_id' => $demoProvider->id,
            'category_id' => $categories->firstWhere('name', 'Plomberie')?->id ?? $categories->random()->id,
            'business_name' => 'Jean-Paul Plomberie',
            'city' => 'Douala',
            'is_verified' => true,
        ]);

        User::factory()->admin()->create([
            'name' => 'Administrateur SmartLink',
            'email' => 'admin@smartlink.cm',
        ]);

        User::factory(10)->client()->create()->each(function (User $client): void {
            ClientProfile::factory()->create(['user_id' => $client->id]);
        });

        User::factory(14)->provider()->create()->each(function (User $provider) use ($categories): void {
            ProviderProfile::factory()->create([
                'user_id' => $provider->id,
                'category_id' => $categories->random()->id,
            ]);
        });
    }
}
