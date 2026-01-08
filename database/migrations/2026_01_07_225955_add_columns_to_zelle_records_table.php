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
        Schema::table('zelle_records', function (Blueprint $table) {
            $table->string('sender_name')->nullable();
            $table->date('zelle_date')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('reference')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status')->default('unused'); // used, partial, unused
            $table->decimal('remaining_balance', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zelle_records', function (Blueprint $table) {
            $table->dropColumn([
                'sender_name',
                'zelle_date',
                'amount',
                'reference',
                'image_path',
                'status',
                'remaining_balance'
            ]);
        });
    }
};
