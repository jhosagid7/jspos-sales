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
        Schema::table('customer_configs', function (Blueprint $table) {
            $table->text('agreement')->nullable()->after('current_batch');
        });

        Schema::table('seller_configs', function (Blueprint $table) {
            $table->text('agreement')->nullable()->after('current_batch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_configs', function (Blueprint $table) {
            $table->dropColumn('agreement');
        });

        Schema::table('seller_configs', function (Blueprint $table) {
            $table->dropColumn('agreement');
        });
    }
};
