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
        Schema::table('configurations', function (Blueprint $table) {
            $table->foreignId('bolsas_warehouse_id')->nullable()->after('soplados_warehouse_id')->constrained('warehouses');
            $table->foreignId('production_materials_warehouse_id')->nullable()->after('bolsas_warehouse_id')->constrained('warehouses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bolsas_warehouse_id');
            $table->dropConstrainedForeignId('production_materials_warehouse_id');
        });
    }
};
