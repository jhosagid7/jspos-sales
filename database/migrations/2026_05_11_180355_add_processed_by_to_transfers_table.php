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
            $table->foreignId('dispatched_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('received_by_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropForeign(['dispatched_by_id']);
            $table->dropForeign(['received_by_id']);
            $table->dropColumn(['dispatched_by_id', 'received_by_id']);
        });
    }
};
