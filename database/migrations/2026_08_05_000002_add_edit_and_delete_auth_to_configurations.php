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
        if (Schema::hasTable('configurations')) {
            Schema::table('configurations', function (Blueprint $table) {
                if (!Schema::hasColumn('configurations', 'email_edit_auth_recipients')) {
                    $table->json('email_edit_auth_recipients')->nullable()->after('whatsapp_credit_auth_users');
                }
                if (!Schema::hasColumn('configurations', 'whatsapp_edit_auth_users')) {
                    $table->json('whatsapp_edit_auth_users')->nullable()->after('email_edit_auth_recipients');
                }
                if (!Schema::hasColumn('configurations', 'email_delete_auth_recipients')) {
                    $table->json('email_delete_auth_recipients')->nullable()->after('whatsapp_edit_auth_users');
                }
                if (!Schema::hasColumn('configurations', 'whatsapp_delete_auth_users')) {
                    $table->json('whatsapp_delete_auth_users')->nullable()->after('email_delete_auth_recipients');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('configurations')) {
            Schema::table('configurations', function (Blueprint $table) {
                $table->dropColumn([
                    'email_edit_auth_recipients',
                    'whatsapp_edit_auth_users',
                    'email_delete_auth_recipients',
                    'whatsapp_delete_auth_users'
                ]);
            });
        }
    }
};
