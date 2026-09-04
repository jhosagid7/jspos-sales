<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bag_productions', function (Blueprint $table) {
            $table->string('status', 30)->default('pending_review')->change();
            $table->foreignId('lifted_by')->nullable()->after('qr_code')->constrained('users')->onDelete('set null');
            $table->dateTime('lifted_at')->nullable()->after('lifted_by');
            $table->foreignId('jspos_production_id')->nullable()->after('lifted_at')->constrained('productions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bag_productions', function (Blueprint $table) {
            $table->dropForeign(['lifted_by']);
            $table->dropForeign(['jspos_production_id']);
            $table->dropColumn([
                'lifted_by',
                'lifted_at',
                'jspos_production_id',
            ]);
        });
    }
};