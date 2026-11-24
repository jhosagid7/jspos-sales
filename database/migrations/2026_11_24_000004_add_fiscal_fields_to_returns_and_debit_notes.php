<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds fiscal tracking fields to sale_returns (Notas de Crédito Fiscal)
 * and to debit_notes (Notas de Débito Fiscal).
 *
 * When a return is processed with a fiscal printer, a Nota de Crédito Fiscal
 * must be emitted — it is ILLEGAL to simply void the invoice.
 *
 * When an extra charge is added to an existing invoice, a Nota de Débito Fiscal
 * must be emitted.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- sale_returns ---
        if (Schema::hasTable('sale_returns')) {
            Schema::table('sale_returns', function (Blueprint $table) {
                $this->addFiscalColumns($table, 'sale_returns');
            });
        }

        // --- debit_notes ---
        if (Schema::hasTable('debit_notes')) {
            Schema::table('debit_notes', function (Blueprint $table) {
                $this->addFiscalColumns($table, 'debit_notes');
            });
        }
    }

    public function down(): void
    {
        $cols = ['fiscal_number', 'fiscal_serial', 'fiscal_z_number', 'fiscal_datetime', 'fiscal_status'];

        if (Schema::hasTable('sale_returns')) {
            Schema::table('sale_returns', function (Blueprint $table) use ($cols) {
                foreach ($cols as $col) {
                    if (Schema::hasColumn('sale_returns', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('debit_notes')) {
            Schema::table('debit_notes', function (Blueprint $table) use ($cols) {
                foreach ($cols as $col) {
                    if (Schema::hasColumn('debit_notes', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }

    private function addFiscalColumns(Blueprint $table, string $tableName): void
    {
        if (!Schema::hasColumn($tableName, 'fiscal_number')) {
            $table->string('fiscal_number', 50)->nullable()
                ->comment('Credit/Debit note number assigned by the fiscal printer');
        }
        if (!Schema::hasColumn($tableName, 'fiscal_serial')) {
            $table->string('fiscal_serial', 20)->nullable()
                ->comment('Printer serial that issued this document');
        }
        if (!Schema::hasColumn($tableName, 'fiscal_z_number')) {
            $table->string('fiscal_z_number', 10)->nullable()
                ->comment('Z counter at time of printing');
        }
        if (!Schema::hasColumn($tableName, 'fiscal_datetime')) {
            $table->timestamp('fiscal_datetime')->nullable()
                ->comment('UTC timestamp of fiscal document emission');
        }
        if (!Schema::hasColumn($tableName, 'fiscal_status')) {
            $table->string('fiscal_status', 20)->nullable()
                ->comment('pending | printed | error | not_applicable');
        }
    }
};
