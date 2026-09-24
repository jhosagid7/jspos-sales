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
        Schema::table('configurations', function (Blueprint $table) {
            if (!Schema::hasColumn('configurations', 'gemini_api_key')) {
                $table->text('gemini_api_key')->nullable()->after('custom_labels');
            }
            if (!Schema::hasColumn('configurations', 'ai_settings')) {
                $table->json('ai_settings')->nullable()->after('gemini_api_key');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            if (Schema::hasColumn('configurations', 'gemini_api_key')) {
                $table->dropColumn('gemini_api_key');
            }
            if (Schema::hasColumn('configurations', 'ai_settings')) {
                $table->dropColumn('ai_settings');
            }
        });
    }
};
