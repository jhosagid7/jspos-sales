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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['day', 'night'])->default('day');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->foreignId('user_id')->constrained('users')->comment('Supervisor who opened shift');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('shift_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_users');
        Schema::dropIfExists('shifts');
    }
};
