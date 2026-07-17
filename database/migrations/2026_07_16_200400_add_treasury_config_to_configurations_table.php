<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->string('treasury_cutoff_hour', 5)->default('17:00')->after('whatsapp_credit_auth_users');
            $table->boolean('treasury_auto_close')->default(true)->after('treasury_cutoff_hour');
        });
    }

    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn(['treasury_cutoff_hour', 'treasury_auto_close']);
        });
    }
};
