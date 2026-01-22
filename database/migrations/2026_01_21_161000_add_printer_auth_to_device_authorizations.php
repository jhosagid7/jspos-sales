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
            $table->boolean('is_network')->default(false)->after('printer_width');
            $table->string('printer_user')->nullable()->after('is_network');
            $table->string('printer_password')->nullable()->after('printer_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_authorizations', function (Blueprint $table) {
            $table->dropColumn(['is_network', 'printer_user', 'printer_password']);
        });
    }
};
