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
        Schema::table('bank_records', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_records', 'income_type')) {
                $table->enum('income_type', ['collection', 'other'])->default('collection')->after('status');
            }
            if (!Schema::hasColumn('bank_records', 'income_category')) {
                $table->string('income_category')->nullable()->after('income_type');
            }
            if (!Schema::hasColumn('bank_records', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('income_category');
            }
        });

        Schema::table('bank_daily_closures', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_daily_closures', 'manual_opening_balance')) {
                $table->decimal('manual_opening_balance', 15, 2)->nullable()->after('opening_balance');
            }
            if (!Schema::hasColumn('bank_daily_closures', 'opening_proof_image')) {
                $table->string('opening_proof_image')->nullable()->after('manual_opening_balance');
            }
            if (!Schema::hasColumn('bank_daily_closures', 'manual_closing_balance')) {
                $table->decimal('manual_closing_balance', 15, 2)->nullable()->after('closing_balance');
            }
            if (!Schema::hasColumn('bank_daily_closures', 'closing_proof_image')) {
                $table->string('closing_proof_image')->nullable()->after('manual_closing_balance');
            }
            if (!Schema::hasColumn('bank_daily_closures', 'opening_difference')) {
                $table->decimal('opening_difference', 15, 2)->default(0.00)->after('closing_proof_image');
            }
            if (!Schema::hasColumn('bank_daily_closures', 'closing_difference')) {
                $table->decimal('closing_difference', 15, 2)->default(0.00)->after('opening_difference');
            }
            if (!Schema::hasColumn('bank_daily_closures', 'opened_at')) {
                $table->timestamp('opened_at')->nullable()->after('closing_difference');
            }
            if (!Schema::hasColumn('bank_daily_closures', 'opened_by')) {
                $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete()->after('opened_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_records', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['income_type', 'income_category', 'created_by']);
        });

        Schema::table('bank_daily_closures', function (Blueprint $table) {
            $table->dropForeign(['opened_by']);
            $table->dropColumn([
                'manual_opening_balance',
                'opening_proof_image',
                'manual_closing_balance',
                'closing_proof_image',
                'opening_difference',
                'closing_difference',
                'opened_at',
                'opened_by'
            ]);
        });
    }
};
