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
            $table->index('created_at');
            $table->index('status');
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->index('product_id');
            $table->index('sale_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['status']);
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['sale_id']);
        });
    }
};
