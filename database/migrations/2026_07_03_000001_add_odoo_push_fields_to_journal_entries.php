<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('odoo_move_id')->nullable()->after('odoo_approved_at');
            $table->text('odoo_push_error')->nullable()->after('odoo_move_id');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn(['odoo_move_id', 'odoo_push_error']);
        });
    }
};
