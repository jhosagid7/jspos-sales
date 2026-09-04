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
        Schema::create('bag_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('shift_type', ['diurno', 'nocturno'])->default('diurno');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->decimal('total_packages', 10, 2)->default(0);
            $table->decimal('total_weight', 12, 4)->default(0);
            $table->text('notes')->nullable();
            $table->string('sync_id', 64)->nullable()->unique();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bag_shifts');
    }
};
