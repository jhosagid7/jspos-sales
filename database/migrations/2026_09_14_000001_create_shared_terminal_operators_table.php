<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'is_shared_terminal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_shared_terminal')->default(false)->after('status');
            });
        }

        if (!Schema::hasTable('terminal_operators')) {
            Schema::create('terminal_operators', function (Blueprint $table) {
                $table->id();
                $table->foreignId('terminal_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('operator_id')->constrained('users')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['terminal_id', 'operator_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('terminal_operators');
        if (Schema::hasColumn('users', 'is_shared_terminal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_shared_terminal');
            });
        }
    }
};