<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('odoo_invoice_id')->nullable()->after('accounting_check');
            $table->string('odoo_invoice_name')->nullable()->after('odoo_invoice_id');
            $table->string('odoo_status')->default('not_exported')->after('odoo_invoice_name');
            $table->text('odoo_push_error')->nullable()->after('odoo_status');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['odoo_invoice_id','odoo_invoice_name','odoo_status','odoo_push_error']);
        });
    }
};
