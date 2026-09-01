<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a Storage (used as the "Environment/Dryer" concept for Smart Sensors)
 * to a ThingsBoard device, and makes the stale-telemetry threshold configurable
 * per environment instead of hard-coded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            if (!Schema::hasColumn('storages', 'thingsboard_device_id')) {
                $table->string('thingsboard_device_id')->nullable()->unique()->after('capacity');
            }
            if (!Schema::hasColumn('storages', 'stale_threshold_minutes')) {
                $table->unsignedInteger('stale_threshold_minutes')->default(15)->after('thingsboard_device_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            if (Schema::hasColumn('storages', 'stale_threshold_minutes')) {
                $table->dropColumn('stale_threshold_minutes');
            }
            if (Schema::hasColumn('storages', 'thingsboard_device_id')) {
                $table->dropColumn('thingsboard_device_id');
            }
        });
    }
};
