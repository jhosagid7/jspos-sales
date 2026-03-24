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
        Schema::table('cargos', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('rejection_reason');
            $table->dateTime('rejection_date')->nullable()->after('rejected_by');
            
            $table->text('deletion_reason')->nullable()->after('rejection_date');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('deletion_reason');
            $table->dateTime('deletion_date')->nullable()->after('deleted_by');
            
            $table->unsignedBigInteger('approved_by')->nullable()->after('status');
            $table->dateTime('approval_date')->nullable()->after('approved_by');
        });

        Schema::table('cargo_details', function (Blueprint $table) {
            $table->json('items_json')->nullable()->after('cost'); // To store variable items before approval
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cargos', function (Blueprint $table) {
            $table->dropColumn(['rejection_reason', 'rejected_by', 'rejection_date', 'deletion_reason', 'deleted_by', 'deletion_date', 'approved_by', 'approval_date']);
        });

        Schema::table('cargo_details', function (Blueprint $table) {
            $table->dropColumn('items_json');
        });
    }
};
