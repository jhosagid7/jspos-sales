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
            $table->boolean('catalogue_show_prices')->default(false)->after('price_list_show_info_block');
            $table->boolean('catalogue_show_base_prices')->default(false)->after('catalogue_show_prices');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn(['catalogue_show_prices', 'catalogue_show_base_prices']);
        });
    }
};
