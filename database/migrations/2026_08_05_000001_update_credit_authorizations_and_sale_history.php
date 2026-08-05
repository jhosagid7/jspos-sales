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
        if (Schema::hasTable('credit_authorizations')) {
            try {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE credit_authorizations MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'pending'");
            } catch (\Throwable $e) {}

            Schema::table('credit_authorizations', function (Blueprint $table) {
                if (!Schema::hasColumn('credit_authorizations', 'action_type')) {
                    $table->string('action_type')->default('credit')->after('status');
                }
                if (!Schema::hasColumn('credit_authorizations', 'recipient_email')) {
                    $table->string('recipient_email')->nullable()->after('action_type');
                }
                if (!Schema::hasColumn('credit_authorizations', 'recipient_phone')) {
                    $table->string('recipient_phone')->nullable()->after('recipient_email');
                }
            });
        }

        if (Schema::hasTable('sale_history_logs')) {
            Schema::table('sale_history_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('sale_history_logs', 'authorized_by_id')) {
                    $table->unsignedBigInteger('authorized_by_id')->nullable()->after('user_id');
                    $table->foreign('authorized_by_id')->references('id')->on('users')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('credit_authorizations')) {
            Schema::table('credit_authorizations', function (Blueprint $table) {
                $table->dropColumn(['action_type', 'recipient_email', 'recipient_phone']);
            });
        }

        if (Schema::hasTable('sale_history_logs')) {
            Schema::table('sale_history_logs', function (Blueprint $table) {
                $table->dropForeign(['authorized_by_id']);
                $table->dropColumn('authorized_by_id');
            });
        }
    }
};
