<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'Minorista' to the enum
        DB::statement("ALTER TABLE customers MODIFY COLUMN type ENUM('Mayoristas', 'Consumidor Final', 'Descuento1', 'Descuento2', 'Otro', 'Minorista') NOT NULL DEFAULT 'Mayoristas'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum (WARNING: This might fail if there are 'Minorista' records)
        // Ideally we should handle data migration here, but for now we just revert definition
        DB::statement("UPDATE customers SET type = 'Mayoristas' WHERE type = 'Minorista'");
        DB::statement("ALTER TABLE customers MODIFY COLUMN type ENUM('Mayoristas', 'Consumidor Final', 'Descuento1', 'Descuento2', 'Otro') NOT NULL DEFAULT 'Mayoristas'");
    }
};
