<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_reports', function (Blueprint $table) {
            $table->id();
            $table->morphs('moderatable');
            $table->enum('verdict', ['clean', 'flagged'])->default('clean');
            // Catégories signalées : coordonnées hors plateforme, propos
            // haineux, arnaque probable, contenu sans rapport, etc.
            $table->json('categories')->nullable();
            $table->text('reason')->nullable();
            $table->string('model', 64);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['verdict', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_reports');
    }
};
