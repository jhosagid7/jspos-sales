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
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 200)->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('name', 200)->change();
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('name', 200)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 85)->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('name', 45)->change();
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('name', 50)->change();
        });
    }
};
