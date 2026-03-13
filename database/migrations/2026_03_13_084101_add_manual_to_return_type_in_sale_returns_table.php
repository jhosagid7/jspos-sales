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
        Schema::table('sale_returns', function (Blueprint $table) {
            DB::statement("ALTER TABLE sale_returns MODIFY COLUMN return_type ENUM('partial', 'full', 'manual') NOT NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            DB::statement("ALTER TABLE sale_returns MODIFY COLUMN return_type ENUM('partial', 'full') NOT NULL");
        });
    }
};
