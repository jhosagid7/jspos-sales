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
        // MySQL specific change for ENUM column
        DB::statement("ALTER TABLE payments MODIFY COLUMN pay_way ENUM('cash', 'deposit', 'nequi', 'zelle', 'wallet') DEFAULT 'cash'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN pay_way ENUM('cash', 'deposit', 'nequi', 'zelle') DEFAULT 'cash'");
    }
};
