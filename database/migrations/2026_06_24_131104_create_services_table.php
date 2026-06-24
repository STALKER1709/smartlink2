<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('service_categories')->restrictOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description');
            $table->decimal('price_amount', 10, 2)->nullable();
            $table->string('price_unit')->nullable();
            $table->string('location')->nullable();
            $table->string('city')->nullable();
            $table->boolean('is_available')->default(true);
            $table->string('availability_note')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['provider_id', 'slug']);
            $table->index(['city', 'category_id', 'status']);
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
