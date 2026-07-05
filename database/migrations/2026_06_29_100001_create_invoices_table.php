<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->integer('billing_id')->nullable();
            $table->integer('customer_id');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('XOF');
            $table->enum('status', ['draft', 'issued', 'paid', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->integer('generated_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('billing_id')->references('id')->on('billings')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('users');
            $table->foreign('generated_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
