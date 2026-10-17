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
            if (!Schema::hasColumn('configurations', 'enable_forma_libre')) {
                $table->boolean('enable_forma_libre')->default(false)->after('pdf_settings');
            }
            if (!Schema::hasColumn('configurations', 'forma_libre_template')) {
                $table->string('forma_libre_template', 50)->default('bucaros')->after('enable_forma_libre');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('configurations', 'enable_forma_libre')) {
                $columnsToDrop[] = 'enable_forma_libre';
            }
            if (Schema::hasColumn('configurations', 'forma_libre_template')) {
                $columnsToDrop[] = 'forma_libre_template';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
