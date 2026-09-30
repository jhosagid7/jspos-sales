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
        if (Schema::hasTable('configurations') && !Schema::hasColumn('configurations', 'multicurrency_enabled')) {
            Schema::table('configurations', function (Blueprint $table) {
                $table->boolean('multicurrency_enabled')->default(true)->after('decimals');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('configurations') && Schema::hasColumn('configurations', 'multicurrency_enabled')) {
            Schema::table('configurations', function (Blueprint $table) {
                $table->dropColumn('multicurrency_enabled');
            });
        }
    }
};
