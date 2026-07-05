<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('journal_entries', 'journal_name')) {
                $table->string('journal_name')->nullable()->after('entry_type');
            }
            if (! Schema::hasColumn('journal_entries', 'document_reference')) {
                $table->string('document_reference')->nullable()->after('journal_name');
            }
            if (! Schema::hasColumn('journal_entries', 'event_type')) {
                $table->string('event_type')->nullable()->after('document_reference');
            }
            if (! Schema::hasColumn('journal_entries', 'provenance_category')) {
                $table->string('provenance_category')->nullable()->after('event_type');
            }
            if (! Schema::hasColumn('journal_entries', 'send_to_odoo')) {
                $table->string('send_to_odoo')->default('Hold for Finance')->after('odoo_status');
            }
            if (! Schema::hasColumn('journal_entries', 'comments')) {
                $table->text('comments')->nullable()->after('odoo_rejection_reason');
            }
        });

        Schema::table('journal_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('journal_lines', 'line_date')) {
                $table->date('line_date')->nullable()->after('journal_entry_id');
            }
            if (! Schema::hasColumn('journal_lines', 'journal')) {
                $table->string('journal')->nullable()->after('line_date');
            }
            if (! Schema::hasColumn('journal_lines', 'document_reference')) {
                $table->string('document_reference')->nullable()->after('journal');
            }
            if (! Schema::hasColumn('journal_lines', 'event_type')) {
                $table->string('event_type')->nullable()->after('document_reference');
            }
            if (! Schema::hasColumn('journal_lines', 'provenance_category')) {
                $table->string('provenance_category')->nullable()->after('event_type');
            }
            if (! Schema::hasColumn('journal_lines', 'description')) {
                $table->string('description', 500)->nullable()->after('provenance_category');
            }
            if (! Schema::hasColumn('journal_lines', 'send_to_odoo')) {
                $table->string('send_to_odoo')->default('Hold for Finance')->after('currency');
            }
            if (! Schema::hasColumn('journal_lines', 'odoo_status')) {
                $table->string('odoo_status')->default('Not exported')->after('send_to_odoo');
            }
            if (! Schema::hasColumn('journal_lines', 'comments')) {
                $table->text('comments')->nullable()->after('odoo_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            foreach ([
                'line_date',
                'journal',
                'document_reference',
                'event_type',
                'provenance_category',
                'description',
                'send_to_odoo',
                'odoo_status',
                'comments',
            ] as $column) {
                if (Schema::hasColumn('journal_lines', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            foreach ([
                'journal_name',
                'document_reference',
                'event_type',
                'provenance_category',
                'send_to_odoo',
                'comments',
            ] as $column) {
                if (Schema::hasColumn('journal_entries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
