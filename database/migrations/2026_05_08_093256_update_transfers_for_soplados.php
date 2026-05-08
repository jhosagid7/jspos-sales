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
        Schema::table('transfers', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('note');
        });

        Schema::table('transfer_details', function (Blueprint $table) {
            $table->decimal('received_quantity', 10, 2)->nullable()->after('quantity');
            $table->decimal('rejected_quantity', 10, 2)->nullable()->after('received_quantity');
        });

        // Update ENUM using DB statement to avoid DBAL issues
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE transfers MODIFY status ENUM('pending', 'dispatched', 'completed', 'completed_partial', 'rejected', 'cancelled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfer_details', function (Blueprint $table) {
            $table->dropColumn(['received_quantity', 'rejected_quantity']);
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });

        \Illuminate\Support\Facades\DB::statement("ALTER TABLE transfers MODIFY status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending'");
    }
};
