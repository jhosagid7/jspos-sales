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
        Schema::table('credit_discount_rules', function (Blueprint $table) {
            $table->string('tag', 20)->nullable()->after('rule_type');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('discount_tag', 20)->nullable()->after('discount_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_discount_rules', function (Blueprint $table) {
            $table->dropColumn('tag');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('discount_tag');
        });
    }
};
