<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Un prestataire ne se met en favori qu'une fois : sans cette
            // contrainte, deux clics rapides sur le cœur créent deux lignes et
            // le compteur ment.
            $table->unique(['user_id', 'provider_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
