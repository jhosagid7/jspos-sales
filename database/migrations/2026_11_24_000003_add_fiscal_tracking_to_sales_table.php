<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the sales table with complete fiscal tracking fields.
 *
 * Already existing (from 2026_10_15 migration):
 *   - document_type   (invoice | delivery_note)
 *   - fiscal_number   (fiscal invoice number)
 *   - is_fiscal       (boolean)
 *
 * New columns:
 *   - fiscal_serial    string   Printer serial number (e.g. 'Z1C0001234')
 *   - fiscal_z_number  string   Z report counter at time of printing (e.g. '0042')
 *   - fiscal_datetime  datetime When the fiscal document was printed
 *   - fiscal_status    string   pending | printed | error | not_applicable
 *   - fiscal_log       json     Full command log for audit (commands sent + printer responses)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'fiscal_serial')) {
                $table->string('fiscal_serial', 20)->nullable()->after('fiscal_number')
                    ->comment('Printer serial that issued this invoice (e.g. Z1C0001234)');
            }
            if (!Schema::hasColumn('sales', 'fiscal_z_number')) {
                $table->string('fiscal_z_number', 10)->nullable()->after('fiscal_serial')
                    ->comment('Z counter at time of printing');
            }
            if (!Schema::hasColumn('sales', 'fiscal_datetime')) {
                $table->timestamp('fiscal_datetime')->nullable()->after('fiscal_z_number')
                    ->comment('UTC timestamp of when the fiscal document was printed');
            }
            if (!Schema::hasColumn('sales', 'fiscal_status')) {
                $table->string('fiscal_status', 20)->nullable()->after('fiscal_datetime')
                    ->comment('pending | printed | error | not_applicable');
            }
            if (!Schema::hasColumn('sales', 'fiscal_log')) {
                $table->json('fiscal_log')->nullable()->after('fiscal_status')
                    ->comment('JSON audit log of all printer commands and responses');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $cols = [
                'fiscal_serial',
                'fiscal_z_number',
                'fiscal_datetime',
                'fiscal_status',
                'fiscal_log',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('sales', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
