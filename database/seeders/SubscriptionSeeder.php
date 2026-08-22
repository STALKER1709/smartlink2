<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Database\Seeder;

/**
 * Sans abonnement, un prestataire est invisible dans les recherches. Les
 * données de démonstration en reçoivent donc un : le compte de démonstration
 * et un tiers des autres au palier Pro, pour que la mise en avant se voie.
 */
class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $essential = Plan::where('code', Plan::CODE_ESSENTIAL)->firstOrFail();
        $pro = Plan::where('code', Plan::CODE_PRO)->firstOrFail();
        $quotas = app(QuotaService::class);

        User::query()
            ->ofRole(User::ROLE_PROVIDER)
            ->with('providerProfile')
            ->get()
            ->each(function (User $provider, int $index) use ($essential, $pro, $quotas): void {
                $isPro = $provider->email === 'provider@smartlink.cm' || $index % 3 === 0;

                Subscription::create([
                    'user_id' => $provider->id,
                    'plan_id' => $isPro ? $pro->id : $essential->id,
                    'status' => Subscription::STATUS_ACTIVE,
                    'starts_at' => now()->subDays(10),
                    'ends_at' => now()->addDays(20),
                ]);

                $quotas->refreshListing($provider);
            });
    }
}
