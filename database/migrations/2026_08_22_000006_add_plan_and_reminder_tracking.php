<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Le palier réglé par ce paiement : il peut différer du palier
            // courant quand le prestataire change de formule.
            $table->foreignId('plan_id')->nullable()->after('subscription_id')->constrained();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            // Dernier seuil de relance envoyé, en jours avant échéance.
            // Évite de renvoyer le même SMS à chaque passage quotidien.
            $table->unsignedTinyInteger('last_reminder_day')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('last_reminder_day');
        });
    }
};
