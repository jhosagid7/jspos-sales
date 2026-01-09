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
        // 1. Create collection_sheets table
        Schema::create('collection_sheets', function (Blueprint $table) {
            $table->id();
            $table->string('sheet_number')->unique(); // Format: YYYYMMDD-01 (Daily sequence)
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        // 2. Add collection_sheet_id to payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('collection_sheet_id')->nullable()->after('user_id');
            $table->foreign('collection_sheet_id')->references('id')->on('collection_sheets');
        });

        // 3. Add zone to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->string('zone')->nullable()->after('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('zone');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['collection_sheet_id']);
            $table->dropColumn('collection_sheet_id');
        });

        Schema::dropIfExists('collection_sheets');
    }
};
