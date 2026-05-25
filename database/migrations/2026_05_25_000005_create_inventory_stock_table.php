<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock', function (Blueprint $table) {
            $table->id();
            $table->integer('storage_id');
            $table->integer('product_id');
            $table->integer('stock_id');
            $table->decimal('quantity', 12, 3)->default(0);
            $table->string('unit');
            $table->string('last_op_id');
            $table->timestamp('last_updated_at')->nullable();

            $table->foreign('storage_id')->references('id')->on('storages')->onDelete('restrict');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('restrict');

            $table->unique(['storage_id', 'product_id', 'stock_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock');
    }
};
