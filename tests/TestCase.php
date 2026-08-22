<?php

namespace Tests;

use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\Subscription;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Donne à un prestataire un abonnement en cours. Publier un service et
     * lire une nouvelle demande en dépendent : sans abonnement, un prestataire
     * de fixture se comporte comme un compte échu.
     */
    protected function subscribeProvider(User $provider, string $planCode = Plan::CODE_PRO): Subscription
    {
        $plan = Plan::firstWhere('code', $planCode)
            ?? ($planCode === Plan::CODE_PRO
                ? Plan::factory()->pro()->create()
                : Plan::factory()->create());

        if ($provider->providerProfile === null) {
            ProviderProfile::factory()->create(['user_id' => $provider->id]);
            $provider->refresh();
        }

        $subscription = Subscription::factory()->create([
            'user_id' => $provider->id,
            'plan_id' => $plan->id,
            'ends_at' => now()->addYear(),
        ]);

        app(QuotaService::class)->refreshListing($provider->refresh());

        return $subscription;
    }
}
