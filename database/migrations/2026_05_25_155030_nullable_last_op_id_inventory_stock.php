<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_stock', function (Blueprint $table) {
            // Allow NULL so backfill rows (seeded from legacy details.qty) can exist
            // without a corresponding inventory_op. Once a sync op touches the row,
            // last_op_id is set to the real op_id.
            $table->string('last_op_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stock', function (Blueprint $table) {
            $table->string('last_op_id')->nullable(false)->change();
        });
    }
};
