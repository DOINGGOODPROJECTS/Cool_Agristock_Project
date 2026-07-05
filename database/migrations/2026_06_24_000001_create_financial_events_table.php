<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_events', function (Blueprint $table) {
            // Identity
            $table->id();
            $table->string('financial_event_id')->unique(); // CAG-FE-{EVENT_TYPE}-{uuid}
            $table->string('idempotency_key')->unique();    // prevents duplicate posting
            $table->string('event_type');                  // controlled by accounting_events config
            $table->string('version')->default('1.0');

            // Source references
            $table->string('source_reference')->nullable(); // stocks.id, billings.id, claims.id, etc.
            $table->string('source_model')->nullable();     // stocks, billings, payments, releases, rottens

            // Customer / partner payload
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('storage_id')->nullable();

            // Financial payload
            $table->decimal('quantity', 14, 4)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('amount', 14, 4);
            $table->string('currency', 10)->default('XOF');
            $table->date('event_date');
            $table->date('due_date')->nullable();
            $table->string('service_type')->nullable();     // storage_fee, drying_fee, handling_fee …
            $table->string('tax_id')->nullable();           // filled after finance approval
            $table->text('notes')->nullable();

            // Accounting lifecycle status
            // draft → export_ready → exported_csv → synced_api → reconciled
            //                                                   → failed
            //                                                   → reversed
            $table->string('accounting_status')->default('draft');
            $table->string('approval_status')->nullable(); // pending, approved, rejected
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Odoo synchronisation metadata
            $table->string('odoo_model')->nullable();        // account.move, account.payment
            $table->string('odoo_record_id')->nullable();    // Odoo internal record ID
            $table->string('odoo_reference')->nullable();    // Odoo invoice/payment number
            $table->string('export_batch_id')->nullable();   // links to export_batches table
            $table->string('sync_error_code')->nullable();
            $table->text('sync_error_message')->nullable();
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->timestamp('last_sync_at')->nullable();

            // Immutable audit trail — no updated_at; use created_at only
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Append-only: no soft deletes on purpose
            $table->index('event_type');
            $table->index('customer_id');
            $table->index('accounting_status');
            $table->index('export_batch_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_events');
    }
};
