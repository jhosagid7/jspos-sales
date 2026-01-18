<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_id')->nullable()->after('user_id');
            $table->enum('delivery_status', ['pending', 'in_transit', 'delivered', 'cancelled'])
                  ->default('pending')
                  ->after('status');
            
            $table->foreign('driver_id')->references('id')->on('users')->onDelete('set null');
            $table->index('delivery_status');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn(['driver_id', 'delivery_status']);
        });
    }
};
