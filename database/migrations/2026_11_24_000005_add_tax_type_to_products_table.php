<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds per-product tax type for fiscal printing.
 *
 * Venezuelan fiscal law requires declaring the IVA rate per item:
 *   exento    = Tax-exempt (no IVA)
 *   general   = Tasa General 16% (Tasa1 in TFHKA)
 *   reducida  = Tasa Reducida 8% (Tasa2 in TFHKA) — for basic food basket items
 *   adicional = Tasa Adicional 31% (Tasa3 in TFHKA) — luxury items
 *
 * Default is 'general' (16%) to match existing behavior.
 * This column is nullable and defaults to null = inherit global VAT setting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'tax_type')) {
                $table->string('tax_type', 20)->nullable()->after('vat')
                    ->comment('exento | general | reducida | adicional — null = inherit global vat setting');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'tax_type')) {
                $table->dropColumn('tax_type');
            }
        });
    }
};
