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
        // Add 'voided' to the ENUM status column
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('approved', 'pending', 'rejected', 'voided') DEFAULT 'approved'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original ENUM status column
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('approved', 'pending', 'rejected') DEFAULT 'approved'");
    }
};
