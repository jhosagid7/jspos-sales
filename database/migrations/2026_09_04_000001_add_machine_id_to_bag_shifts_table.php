<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bag_shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('bag_shifts', 'machine_id')) {
                $table->foreignId('machine_id')
                      ->nullable()
                      ->after('user_id')
                      ->constrained('bag_machines')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bag_shifts', function (Blueprint $table) {
            if (Schema::hasColumn('bag_shifts', 'machine_id')) {
                $table->dropForeign(['machine_id']);
                $table->dropColumn('machine_id');
            }
        });
    }
};
