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
            // Change enum to string to support 'zelle', 'mixed', etc.
            $table->string('type')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Revert to enum if needed (though data loss might occur for new types)
            // Ideally we wouldn't revert this in production without care
            // $table->enum('type', ['credit', 'cash', 'deposit', 'nequi', 'cash/nequi'])->default('cash')->change();
        });
    }
};
