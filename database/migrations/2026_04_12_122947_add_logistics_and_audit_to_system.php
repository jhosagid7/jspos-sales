<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add Logistics fields to Users
        Schema::table('users', function (Blueprint $blueprint) {
            $blueprint->dateTime('order_deadline_at')->nullable()->comment('Fecha y hora límite de carga de pedidos');
            $blueprint->boolean('is_deadline_active')->default(false)->comment('Activa el bloqueo por horario');
        });

        // 2. Add 'draft' status to Orders table (handling ENUM migration)
        // Note: For MariaDB/MySQL we use raw DB change to modify ENUM without losing data
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('processed', 'deleted', 'pending', 'draft') DEFAULT 'draft'");

        // 3. Create Audit Logs table
        Schema::create('order_history_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id');
            $table->string('action')->comment('created, edited, status_changed, deleted');
            $table->json('details')->nullable()->comment('Snapshot de cambios');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_history_logs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['order_deadline_at', 'is_deadline_active']);
        });

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('processed', 'deleted', 'pending') DEFAULT 'pending'");
    }
};
