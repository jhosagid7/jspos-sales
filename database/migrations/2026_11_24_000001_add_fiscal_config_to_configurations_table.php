<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds fiscal printer configuration fields to the configurations table.
 *
 * New columns:
 *   - fiscal_printer_enabled  boolean  Whether fiscal printing is active for this installation
 *   - is_special_taxpayer     boolean  Whether this business is a Contribuyente Especial (IGTF applies)
 *   - fiscal_printer_model    string   'hka80' | 'pp9' | 'pp9plus'
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            if (!Schema::hasColumn('configurations', 'fiscal_printer_enabled')) {
                $table->boolean('fiscal_printer_enabled')->default(false)->after('vat')
                    ->comment('Master switch: enables fiscal printer integration for this installation');
            }
            if (!Schema::hasColumn('configurations', 'is_special_taxpayer')) {
                $table->boolean('is_special_taxpayer')->default(false)->after('fiscal_printer_enabled')
                    ->comment('Contribuyente Especial SENIAT: enables IGTF 3% on foreign currency payments');
            }
            if (!Schema::hasColumn('configurations', 'fiscal_printer_model')) {
                $table->string('fiscal_printer_model', 20)->nullable()->after('is_special_taxpayer')
                    ->comment('Printer model: hka80 | pp9 | pp9plus');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $cols = ['fiscal_printer_enabled', 'is_special_taxpayer', 'fiscal_printer_model'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('configurations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
