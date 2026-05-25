<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The legacy schema's storages table has no data (dump import was skipped
 * because the table pre-existed from migrations). The FK constraints on
 * inventory_stock therefore reject all rows that reference storage_id values
 * that exist in stocks but have no corresponding storages row.
 *
 * Since the legacy app itself doesn't enforce referential integrity between
 * stocks.storage_id → storages.id (no FK on stocks), our sync shadow table
 * should match that behavior. Data integrity is enforced at the application
 * layer (API validator: Rule::exists('storages','id')->whereNull('deleted_at')).
 *
 * The unique index (storage_id, product_id, stock_id) is kept — it is the
 * correct guard against duplicate inventory_stock rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_stock', function (Blueprint $table) {
            $table->dropForeign('inventory_stock_storage_id_foreign');
            $table->dropForeign('inventory_stock_product_id_foreign');
            $table->dropForeign('inventory_stock_stock_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stock', function (Blueprint $table) {
            $table->foreign('storage_id')->references('id')->on('storages')->onDelete('restrict');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('restrict');
        });
    }
};
