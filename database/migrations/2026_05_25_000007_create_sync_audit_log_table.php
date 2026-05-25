<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('op_id');
            $table->integer('actor_id');
            $table->integer('actor_group_id');
            $table->enum('action', [
                'submitted',
                'applied',
                'reconciled',
                'conflict_flagged',
                'accepted',
                'discarded',
                'cancelled',
                'merged',
                'edited',
                'overridden',
            ]);
            $table->json('before_value')->nullable();
            $table->json('after_value')->nullable();
            $table->text('reason')->nullable();
            $table->string('device_id');
            $table->string('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('actor_id')->references('id')->on('users')->onDelete('restrict');

            $table->index('op_id');
            $table->index('actor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_audit_log');
    }
};
