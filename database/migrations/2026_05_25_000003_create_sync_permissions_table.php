<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_permissions', function (Blueprint $table) {
            $table->id();
            $table->integer('group_id');
            $table->string('action');
            $table->boolean('allowed')->default(false);
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->unique(['group_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_permissions');
    }
};
