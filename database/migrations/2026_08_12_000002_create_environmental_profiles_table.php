<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product environmental profiles (target temperature/RH/airflow ranges used
 * by the Smart Sensor Management module). One profile per product for V1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environmental_profiles', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->decimal('min_temperature', 5, 2)->nullable();
            $table->decimal('max_temperature', 5, 2)->nullable();
            $table->decimal('max_rh', 5, 2)->nullable();
            $table->decimal('min_airflow', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environmental_profiles');
    }
};
