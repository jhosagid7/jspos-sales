<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_records', function (Blueprint $table) {
            $table->foreignId('debit_note_id')->nullable()->after('sale_id')->constrained();
        });
    }

    public function down(): void
    {
        Schema::table('bank_records', function (Blueprint $table) {
            $table->dropForeign(['debit_note_id']);
            $table->dropColumn('debit_note_id');
        });
    }
};
