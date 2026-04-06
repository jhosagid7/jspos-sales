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
        Schema::table('banks', function (Blueprint $table) {
            $table->string('account_number')->after('name');
            $table->string('cedula')->after('account_number');
            $table->string('phone')->after('cedula');
        });

        Schema::create('bank_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_user');
        
        Schema::table('banks', function (Blueprint $table) {
            $table->dropColumn(['account_number', 'cedula', 'phone']);
        });
    }
};
