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
        Schema::table('device_authorizations', function (Blueprint $table) {
            $table->string('printer_name')->nullable()->after('status');
            $table->string('printer_width')->default('80mm')->after('printer_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_authorizations', function (Blueprint $table) {
            $table->dropColumn(['printer_name', 'printer_width']);
        });
    }
};
