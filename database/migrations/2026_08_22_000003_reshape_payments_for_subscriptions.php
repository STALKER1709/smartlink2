<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L'acompte client → prestataire est retiré du produit : le seul flux d'argent
 * restant est l'abonnement prestataire → SmartLink. La table est redéfinie
 * plutôt que rapiécée — elle n'a jamais contenu que des acomptes de bac à sable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('payments');

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_xaf');
            $table->enum('operator', ['mtn', 'orange'])->nullable();
            $table->string('phone', 20);
            $table->enum('status', ['pending', 'success', 'failed', 'cancelled'])->default('pending');
            $table->string('campay_reference')->nullable()->unique();
            $table->string('internal_reference')->unique();
            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
