<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            ServiceCategorySeeder::class,
            UserSeeder::class,
            SubscriptionSeeder::class,
            ServiceSeeder::class,
            RequestSeeder::class,
        ]);
    }
}
