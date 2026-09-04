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
        Schema::table('bag_productions', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->onDelete('set null');
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('rejection_reason')->nullable()->after('reviewed_at');
            $table->decimal('original_weight', 12, 4)->nullable()->after('rejection_reason');
            $table->string('qr_code', 100)->nullable()->after('original_weight')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bag_productions', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'reviewed_by',
                'reviewed_at',
                'rejection_reason',
                'original_weight',
                'qr_code',
            ]);
        });
    }
};