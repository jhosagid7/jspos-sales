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
            $table->text('email_rate_recipients')->nullable()->after('whatsapp_weekly_report_groups');
            $table->text('email_closure_recipients')->nullable()->after('email_rate_recipients');
            $table->text('email_weekly_report_recipients')->nullable()->after('email_closure_recipients');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn([
                'email_rate_recipients',
                'email_closure_recipients',
                'email_weekly_report_recipients'
            ]);
        });
    }
};
