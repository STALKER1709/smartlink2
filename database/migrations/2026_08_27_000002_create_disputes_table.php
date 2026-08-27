<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 40);
            $table->text('description');

            /*
             * Jusqu'à trois photos, sans ordre ni légende : une colonne JSON
             * suffit là où une table de pièces jointes n'apporterait qu'une
             * jointure. Le jour où une preuve devra porter une date ou un
             * auteur distinct, elle deviendra une table.
             */
            $table->json('evidence_paths')->nullable();

            $table->string('status', 20)->default('open');
            $table->text('resolution')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // La file d'attente de l'administration se lit par statut, du plus
            // récent au plus ancien.
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
