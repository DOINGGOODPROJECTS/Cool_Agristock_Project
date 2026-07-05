<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('document_reference');
            $table->string('customer_name')->nullable()->after('customer_id');
        });
    }
    public function down(): void {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropColumn(['customer_id', 'customer_name']);
        });
    }
};
