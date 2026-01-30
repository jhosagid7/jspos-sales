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
            $table->timestamp('deletion_requested_at')->nullable();
            $table->text('deletion_reason')->nullable();
            $table->unsignedBigInteger('deletion_requested_by')->nullable();
            $table->unsignedBigInteger('deletion_approved_by')->nullable();
            $table->timestamp('deletion_approved_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'deletion_requested_at',
                'deletion_reason',
                'deletion_requested_by',
                'deletion_approved_by',
                'deletion_approved_at'
            ]);
        });
    }
};
