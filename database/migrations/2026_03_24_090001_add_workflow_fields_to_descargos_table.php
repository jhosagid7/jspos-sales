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
        Schema::table('descargos', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
            
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approval_date')->nullable();
            
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->dateTime('rejection_date')->nullable();
            
            $table->text('deletion_reason')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->dateTime('deletion_date')->nullable();
            
            // Foreign keys
            $table->foreign('approved_by')->references('id')->on('users');
            $table->foreign('rejected_by')->references('id')->on('users');
            $table->foreign('deleted_by')->references('id')->on('users');
        });

        Schema::table('descargo_details', function (Blueprint $table) {
            $table->json('items_json')->nullable()->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('descargos', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['deleted_by']);
            
            $table->dropColumn([
                'approved_by', 'approval_date', 'rejection_reason', 'rejected_by', 'rejection_date',
                'deletion_reason', 'deleted_by', 'deletion_date'
            ]);
        });

        Schema::table('descargo_details', function (Blueprint $table) {
            $table->dropColumn('items_json');
        });
    }
};
