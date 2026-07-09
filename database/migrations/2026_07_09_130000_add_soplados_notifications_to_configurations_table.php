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
            $table->text('whatsapp_soplados_shift_groups')->nullable();
            $table->text('whatsapp_soplados_shift_users')->nullable();
            $table->text('whatsapp_soplados_weekly_groups')->nullable();
            $table->text('whatsapp_soplados_weekly_users')->nullable();
            $table->text('email_soplados_weekly_recipients')->nullable();
            $table->integer('weekly_report_send_day')->default(6); // 0 = Sunday, 6 = Saturday
            $table->string('weekly_report_send_hour')->default('10:00');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_soplados_shift_groups',
                'whatsapp_soplados_shift_users',
                'whatsapp_soplados_weekly_groups',
                'whatsapp_soplados_weekly_users',
                'email_soplados_weekly_recipients',
                'weekly_report_send_day',
                'weekly_report_send_hour'
            ]);
        });
    }
};
