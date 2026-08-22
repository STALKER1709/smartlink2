<?php

namespace App\Console\Commands;

use App\Services\QuotaService;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class RefreshSubscriptions extends Command
{
    protected $signature = 'subscriptions:refresh';

    protected $description = 'Expire les abonnements échus et recalcule la visibilité des prestataires';

    public function handle(SubscriptionService $subscriptions, QuotaService $quotas): int
    {
        $reminded = $subscriptions->sendExpiryReminders();
        $expired = $subscriptions->expireLapsed();

        // Le compteur mensuel repart de zéro au changement de mois : ce passage
        // fait réapparaître dans les recherches ceux qui étaient au plafond.
        $counts = $quotas->refreshAllListings();

        $this->info("Relances envoyées : {$reminded}");
        $this->info("Abonnements expirés : {$expired}");
        $this->info("Prestataires visibles : {$counts['listed']} — masqués : {$counts['unlisted']}");

        return self::SUCCESS;
    }
}
