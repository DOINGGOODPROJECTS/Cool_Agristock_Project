<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_ops', function (Blueprint $table) {
            $table->uuid('op_id')->primary();
            $table->integer('user_id');
            $table->string('device_id');
            $table->unsignedBigInteger('logical_seq');
            $table->integer('storage_id');
            $table->integer('product_id');
            $table->integer('stock_id')->nullable();
            $table->enum('op_type', ['stock_in', 'stock_out', 'adjustment', 'spoilage', 'transfer']);
            $table->decimal('quantity_delta', 12, 3);
            $table->string('unit');
            $table->text('notes')->nullable();
            $table->enum('sync_status', ['pending', 'applied', 'conflict', 'superseded', 'cancelled'])->default('pending');
            $table->timestamp('client_created_at')->nullable();
            $table->timestamp('server_received_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->string('conflict_with_op_id')->nullable();
            $table->text('conflict_reason')->nullable();
            $table->integer('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->integer('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('edited_from_op_id')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('storage_id')->references('id')->on('storages')->onDelete('restrict');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('set null');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['user_id', 'logical_seq']);
            $table->index('sync_status');
            $table->index('storage_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_ops');
    }
};
