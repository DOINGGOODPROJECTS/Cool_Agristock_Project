<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->unique(); // CAG-EXPORT-{YYYYMMDD}-{uuid4}

            // What this batch contains
            $table->string('export_type');           // contacts, invoices, payments, zip_package
            $table->string('status')->default('pending'); // pending, generating, ready, downloaded, failed

            // File metadata (CSV-06)
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_checksum')->nullable(); // SHA-256 of generated file
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedBigInteger('file_size_bytes')->nullable();

            // Period covered
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();

            // Filters applied
            $table->json('filters')->nullable();

            // Validation summary (CSV-07)
            $table->unsignedInteger('validation_errors')->default(0);
            $table->text('validation_report')->nullable();

            $table->string('error_message')->nullable();

            // Audit
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();

            $table->timestamps();

            $table->index('export_type');
            $table->index('status');
            $table->index('generated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_batches');
    }
};
