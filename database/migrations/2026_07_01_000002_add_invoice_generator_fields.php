<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'stock_lot')) {
                $table->string('stock_lot')->nullable()->after('due_date');
            }
            if (! Schema::hasColumn('invoices', 'payment_terms')) {
                $table->string('payment_terms')->nullable()->after('stock_lot');
            }
            if (! Schema::hasColumn('invoices', 'odoo_partner_ref')) {
                $table->string('odoo_partner_ref')->nullable()->after('payment_terms');
            }
            if (! Schema::hasColumn('invoices', 'finance_status')) {
                $table->string('finance_status')->default('To review')->after('status');
            }
            if (! Schema::hasColumn('invoices', 'send_to_odoo')) {
                $table->string('send_to_odoo')->default('Hold for Finance')->after('finance_status');
            }
            if (! Schema::hasColumn('invoices', 'odoo_decision_reason')) {
                $table->text('odoo_decision_reason')->nullable()->after('send_to_odoo');
            }
            if (! Schema::hasColumn('invoices', 'accounting_check')) {
                $table->string('accounting_check')->default('OK')->after('odoo_decision_reason');
            }
        });

        if (! Schema::hasTable('invoice_lines')) {
            Schema::create('invoice_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->unsignedInteger('line_no');
                $table->string('service')->nullable();
                $table->string('category')->nullable();
                $table->string('product')->nullable();
                $table->string('description', 500)->nullable();
                $table->string('unit')->nullable();
                $table->decimal('quantity', 14, 4)->default(0);
                $table->decimal('unit_price', 15, 2)->default(0);
                $table->decimal('discount_fixed_amount', 15, 2)->default(0);
                $table->decimal('amount_before_vat', 15, 2)->default(0);
                $table->decimal('vat_rate', 8, 4)->default(0);
                $table->decimal('vat_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->string('journal_entry_no')->nullable();
                $table->string('send_to_odoo')->default('Hold for Finance');
                $table->text('odoo_decision_reason')->nullable();
                $table->text('comments')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');

        Schema::table('invoices', function (Blueprint $table) {
            foreach ([
                'stock_lot',
                'payment_terms',
                'odoo_partner_ref',
                'finance_status',
                'send_to_odoo',
                'odoo_decision_reason',
                'accounting_check',
            ] as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
