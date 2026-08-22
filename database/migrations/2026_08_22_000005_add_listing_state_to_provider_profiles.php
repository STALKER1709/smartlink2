<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * État de visibilité dénormalisé. Le calculer à la volée obligerait chaque
 * recherche à corréler l'abonnement, son palier et le compteur mensuel :
 * on le recalcule sur événement et une fois par jour, et les recherches
 * n'ont plus qu'un booléen indexé à filtrer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->boolean('is_listed')->default(false)->after('is_verified');
            // Mise en avant dans les résultats, ouverte par le palier Pro.
            $table->boolean('is_promoted')->default(false)->after('is_listed');
            $table->unsignedInteger('requests_read_count')->default(0)->after('is_listed');
            $table->string('requests_read_period', 7)->nullable()->after('requests_read_count');

            $table->index('is_listed');
        });
    }

    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropIndex(['is_listed']);
            $table->dropColumn(['is_listed', 'is_promoted', 'requests_read_count', 'requests_read_period']);
        });
    }
};
