<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('description', 500);
            $table->date('entry_date');
            $table->enum('entry_type', ['manual', 'auto_billing', 'auto_payment', 'claim', 'adjustment'])->default('manual');
            $table->enum('status', ['draft', 'submitted', 'approved', 'posted', 'rejected'])->default('draft');

            // Polymorphic source (Billing, Payment, Claim, etc.)
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->index(['source_type', 'source_id']);

            $table->foreignId('financial_event_id')->nullable()->constrained('financial_events')->nullOnDelete();

            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->string('currency', 3)->default('XOF');

            // Odoo authorization
            $table->enum('odoo_status', [
                'not_queued',
                'pending_admin_approval',
                'approved_for_odoo',
                'exported',
                'synced',
                'rejected',
            ])->default('not_queued');
            $table->integer('odoo_approved_by')->nullable();
            $table->timestamp('odoo_approved_at')->nullable();
            $table->text('odoo_rejection_reason')->nullable();

            // Audit trail
            $table->integer('created_by');
            $table->integer('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->integer('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();

            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');
            $table->foreign('posted_by')->references('id')->on('users');
            $table->foreign('odoo_approved_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
