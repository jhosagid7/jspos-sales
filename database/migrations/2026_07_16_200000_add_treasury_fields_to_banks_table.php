<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->boolean('is_tracked')->default(false)->after('currency_code');
            $table->decimal('initial_balance', 15, 2)->default(0)->after('is_tracked');
            $table->date('initial_balance_date')->nullable()->after('initial_balance');
            $table->decimal('current_balance', 15, 2)->default(0)->after('initial_balance_date');
        });
    }

    public function down(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->dropColumn(['is_tracked', 'initial_balance', 'initial_balance_date', 'current_balance']);
        });
    }
};
