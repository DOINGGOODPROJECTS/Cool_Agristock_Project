<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A drying batch is the process-level record tying an environment (dryer),
 * a product and its environmental profile, and (optionally) the customer
 * whose goods are being dried, together for a period of time. This is
 * distinct from `stocks`, which tracks post-drying warehousing of a
 * customer's quantity — different lifecycle, so it stays its own table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drying_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code')->unique();
            $table->integer('storage_id');
            $table->integer('product_id');
            $table->unsignedBigInteger('environmental_profile_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('operator_id')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->enum('status', ['in_progress', 'completed', 'cancelled'])->default('in_progress');
            $table->string('outcome')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('storage_id')->references('id')->on('storages')->onDelete('restrict');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('environmental_profile_id')->references('id')->on('environmental_profiles')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('operator_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['storage_id', 'status']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drying_batches');
    }
};
