<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->unsignedInteger('price_xaf');
            // null = illimité
            $table->unsignedInteger('max_services')->nullable();
            $table->unsignedInteger('max_monthly_requests')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('has_ai_writing')->default(false);
            $table->boolean('has_stats')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
