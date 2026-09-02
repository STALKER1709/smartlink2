<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Marque le moment où un règlement a réellement prolongé l'abonnement.
 *
 * Sans cette trace, « ce paiement a-t-il déjà été crédité ? » n'avait pas de
 * réponse dans la base : le statut « success » dit que l'argent est arrivé, pas
 * qu'un cycle a été accordé. Rejouer un règlement en offrait un second.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('credited_at')->nullable()->after('paid_at');
        });

        // Les règlements déjà aboutis ont, par construction, déjà été crédités :
        // sans ce rattrapage, un rappel tardif sur l'un d'eux offrirait un cycle.
        DB::table('payments')
            ->where('status', 'success')
            ->whereNull('credited_at')
            ->update(['credited_at' => DB::raw('coalesce(paid_at, updated_at)')]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('credited_at');
        });
    }
};
