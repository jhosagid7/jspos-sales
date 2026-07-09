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
            $table->text('whatsapp_rate_users')->nullable()->after('email_weekly_report_recipients');
            $table->text('whatsapp_closure_users')->nullable()->after('whatsapp_rate_users');
            $table->text('whatsapp_weekly_report_users')->nullable()->after('whatsapp_closure_users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_rate_users',
                'whatsapp_closure_users',
                'whatsapp_weekly_report_users'
            ]);
        });
    }
};
