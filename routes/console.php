<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Passage quotidien : bascule les abonnements échus en « expiré » et remet
// à jour la visibilité des prestataires, notamment au changement de mois.
Schedule::command('subscriptions:refresh')->dailyAt('02:00');
