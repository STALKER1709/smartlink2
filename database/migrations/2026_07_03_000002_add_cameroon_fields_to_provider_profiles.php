<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->string('quarter')->nullable()->after('city');
            $table->decimal('latitude', 10, 7)->nullable()->after('quarter');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('whatsapp')->nullable()->after('longitude');
            $table->string('id_card_path')->nullable()->after('logo_path');
            $table->boolean('id_card_verified')->default(false)->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropColumn(['quarter', 'latitude', 'longitude', 'whatsapp', 'id_card_path', 'id_card_verified']);
        });
    }
};
