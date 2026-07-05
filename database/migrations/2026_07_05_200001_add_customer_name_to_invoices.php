<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->integer('customer_id')->nullable()->change();
            $table->string('customer_name')->nullable()->after('customer_id');
            $table->foreign('customer_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->integer('customer_id')->nullable(false)->change();
            $table->dropColumn('customer_name');
            $table->foreign('customer_id')->references('id')->on('users');
        });
    }
};
