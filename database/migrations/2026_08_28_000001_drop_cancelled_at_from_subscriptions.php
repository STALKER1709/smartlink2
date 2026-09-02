<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retire `cancelled_at` de la table des abonnements.
 *
 * La colonne accompagnait un statut « cancelled » qu'aucun chemin du code
 * n'atteignait : ni action du prestataire, ni tâche planifiée. Elle n'était
 * jamais lue, et les deux seules écritures y posaient `null` — elle ne
 * contient donc que des valeurs nulles, et sa suppression ne perd aucune
 * donnée.
 *
 * L'énumération de `status`, elle, garde « cancelled » parmi ses valeurs
 * permises, et c'est délibéré : rétrécir une contrainte échoue sur toute ligne
 * qui porterait déjà cette valeur, et rien ne permet de l'exclure depuis un
 * poste de développement. La valeur est inerte — aucun chemin ne l'écrit, et
 * `isUsable()` filtre par liste blanche, donc une telle ligne n'ouvre aucun
 * droit. `SubscriptionTest` le vérifie.
 *
 * Le jour où la résiliation explicite deviendra une vraie fonctionnalité,
 * elle reviendra avec son chemin d'écriture, son écran et ses tests.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subscriptions', 'cancelled_at')) {
            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('cancelled_at');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscriptions', 'cancelled_at')) {
            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('ends_at');
        });
    }
};
