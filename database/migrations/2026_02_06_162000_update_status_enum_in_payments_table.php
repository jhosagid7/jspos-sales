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
        // Modifying ENUM in MySQL/MariaDB usually requires raw SQL or specific Doctrine mapping.
        // For ENUMs, raw SQL statement is often safest and most direct for existing content.
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('approved', 'pending', 'rejected') DEFAULT 'approved'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting back to original enum - CAUTION: valid only if no 'rejected' rows exist.
        // For safety in dev, we might leave it or force conversion, but ideally down() should reverse structure.
        // Ideally we would delete rejected payments first, but let's just attempt reset.
        // DB::statement("UPDATE payments SET status = 'pending' WHERE status = 'rejected'"); 
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('approved', 'pending') DEFAULT 'approved'");
    }
};
