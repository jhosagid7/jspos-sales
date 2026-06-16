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
            $table->text('soplados_email_recipients')->nullable()->after('production_email_body');
            $table->string('soplados_email_subject')->nullable()->after('soplados_email_recipients');
            $table->text('soplados_email_body')->nullable()->after('soplados_email_subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn(['soplados_email_recipients', 'soplados_email_subject', 'soplados_email_body']);
        });
    }
};
