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
        if (Schema::hasTable('configurations') && !Schema::hasColumn('configurations', 'custom_labels')) {
            Schema::table('configurations', function (Blueprint $table) {
                $table->json('custom_labels')->nullable()->after('pdf_settings');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('configurations') && Schema::hasColumn('configurations', 'custom_labels')) {
            Schema::table('configurations', function (Blueprint $table) {
                $table->dropColumn('custom_labels');
            });
        }
    }
};
