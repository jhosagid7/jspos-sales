<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'document_type')) {
                $table->string('document_type', 30)->default('invoice')->after('invoice_number')->index();
            }
            if (!Schema::hasColumn('sales', 'fiscal_number')) {
                $table->string('fiscal_number', 50)->nullable()->after('document_type')->index();
            }
            if (!Schema::hasColumn('sales', 'is_fiscal')) {
                $table->boolean('is_fiscal')->default(false)->after('fiscal_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('sales', 'document_type')) $cols[] = 'document_type';
            if (Schema::hasColumn('sales', 'fiscal_number')) $cols[] = 'fiscal_number';
            if (Schema::hasColumn('sales', 'is_fiscal')) $cols[] = 'is_fiscal';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
