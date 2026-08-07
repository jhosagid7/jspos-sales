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
        Schema::table('commission_goals', function (Blueprint $table) {
            if (!Schema::hasColumn('commission_goals', 'start_day_of_week')) {
                $table->string('start_day_of_week', 20)->default('lunes')->after('periodicity');
            }
            if (!Schema::hasColumn('commission_goals', 'end_day_of_week')) {
                $table->string('end_day_of_week', 20)->default('domingo')->after('start_day_of_week');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_goals', function (Blueprint $table) {
            if (Schema::hasColumn('commission_goals', 'start_day_of_week')) {
                $table->dropColumn('start_day_of_week');
            }
            if (Schema::hasColumn('commission_goals', 'end_day_of_week')) {
                $table->dropColumn('end_day_of_week');
            }
        });
    }
};
