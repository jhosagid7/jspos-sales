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
        DB::statement("ALTER TABLE `sales` MODIFY COLUMN `type` ENUM('credit', 'cash', 'deposit', 'nequi', 'cash/nequi', 'mixed', 'bank') DEFAULT 'cash'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `sales` MODIFY COLUMN `type` ENUM('credit', 'cash', 'deposit', 'nequi', 'cash/nequi') DEFAULT 'cash'");
    }
};
