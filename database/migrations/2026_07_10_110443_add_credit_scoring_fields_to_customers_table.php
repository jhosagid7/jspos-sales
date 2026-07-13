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
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('credit_score')->default(100)->nullable();
            $table->string('credit_status')->default('new')->nullable();
            $table->decimal('credit_limit_recommended', 12, 2)->default(0.00)->nullable();
            $table->timestamp('last_credit_scoring_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['credit_score', 'credit_status', 'credit_limit_recommended', 'last_credit_scoring_at']);
        });
    }
};
