<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_sessions', function (Blueprint $table) {
            $table->uuid('session_id')->primary();
            $table->integer('user_id');
            $table->string('device_id');
            $table->bigInteger('client_logical_seq')->default(0);
            $table->unsignedInteger('ops_submitted')->default(0);
            $table->unsignedInteger('ops_applied')->default(0);
            $table->unsignedInteger('ops_conflicted')->default(0);
            $table->enum('status', ['in_progress', 'completed', 'failed'])->default('in_progress');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_sessions');
    }
};
