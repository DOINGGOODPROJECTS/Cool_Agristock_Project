<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'cooperative_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('cooperative_id')->nullable()->after('group_id')
                      ->constrained('cooperatives')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('storages', 'cooperative_id')) {
            Schema::table('storages', function (Blueprint $table) {
                $table->foreignId('cooperative_id')->nullable()->after('id')
                      ->constrained('cooperatives')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('products', 'cooperative_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('cooperative_id')->nullable()->after('id')
                      ->constrained('cooperatives')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['cooperative_id']);
            $table->dropColumn('cooperative_id');
        });

        Schema::table('storages', function (Blueprint $table) {
            $table->dropForeign(['cooperative_id']);
            $table->dropColumn('cooperative_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['cooperative_id']);
            $table->dropColumn('cooperative_id');
        });
    }
};
