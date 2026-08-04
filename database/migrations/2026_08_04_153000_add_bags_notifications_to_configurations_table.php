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
            $table->text('whatsapp_bags_shift_groups')->nullable();
            $table->text('whatsapp_bags_shift_users')->nullable();
            $table->text('whatsapp_bags_admin_groups')->nullable();
            $table->text('whatsapp_bags_admin_users')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_bags_shift_groups',
                'whatsapp_bags_shift_users',
                'whatsapp_bags_admin_groups',
                'whatsapp_bags_admin_users',
            ]);
        });
    }
};
