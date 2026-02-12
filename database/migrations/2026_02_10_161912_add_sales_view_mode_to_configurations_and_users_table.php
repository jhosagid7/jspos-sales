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
        Schema::table('configurations', function (Blueprint $table) {
            $table->string('sales_view_mode')->default('grid')->after('printer_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('sales_view_mode')->nullable()->after('theme');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn('sales_view_mode');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sales_view_mode');
        });
    }
};
