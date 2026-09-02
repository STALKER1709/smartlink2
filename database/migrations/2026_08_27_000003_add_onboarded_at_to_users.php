<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une date plutôt qu'un booléen : elle dit aussi *quand*, ce qui permettra
     * de savoir si un compte a vu la version actuelle de l'accueil ou une
     * ancienne, sans ajouter de colonne le jour où celle-ci changera.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('onboarded_at')->nullable()->after('email_verified_at');
        });

        /*
         * Les comptes déjà là connaissent la plateforme : leur imposer l'accueil
         * à la prochaine visite serait une régression, pas une nouveauté. On
         * les marque à leur date d'inscription.
         */
        DB::table('users')->whereNull('onboarded_at')->update(['onboarded_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('onboarded_at');
        });
    }
};
