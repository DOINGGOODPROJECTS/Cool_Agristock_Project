<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a lower humidity bound alongside the existing max_rh ceiling, so a
 * product's recommended RH can be expressed as a full range (min-max)
 * rather than a ceiling only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environmental_profiles', function (Blueprint $table) {
            $table->decimal('min_rh', 5, 2)->nullable()->after('max_temperature');
        });
    }

    public function down(): void
    {
        Schema::table('environmental_profiles', function (Blueprint $table) {
            $table->dropColumn('min_rh');
        });
    }
};
