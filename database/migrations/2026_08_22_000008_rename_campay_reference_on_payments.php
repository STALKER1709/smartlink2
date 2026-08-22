<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le fournisseur d'encaissement passe de Campay à HR-Skills Pay : la colonne
 * ne doit plus porter le nom d'un prestataire particulier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->renameColumn('campay_reference', 'provider_reference');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->renameColumn('provider_reference', 'campay_reference');
        });
    }
};
