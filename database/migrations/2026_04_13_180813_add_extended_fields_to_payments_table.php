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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('issuer_name')->nullable()->after('bank');
            $table->string('zelle_image')->nullable()->after('payment_date');
            $table->string('bank_image')->nullable()->after('zelle_image');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['issuer_name', 'zelle_image', 'bank_image']);
        });
    }
};
