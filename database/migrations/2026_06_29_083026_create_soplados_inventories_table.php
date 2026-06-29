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
        Schema::create('soplados_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('supervisor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('operator_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('set null');
            $table->string('status')->default('pending_acceptance'); // pending_acceptance, completed, rejected
            $table->text('notes')->nullable();
            $table->text('operator_notes')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('soplados_inventory_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soplados_inventory_id')->constrained('soplados_inventories')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('type'); // finished_product, raw_material
            $table->double('system_stock_primera');
            $table->double('counted_primera');
            $table->double('difference_primera');
            $table->double('system_stock_segunda')->nullable();
            $table->double('counted_segunda')->nullable();
            $table->double('difference_segunda')->nullable();
            $table->double('counted_merma')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soplados_inventory_details');
        Schema::dropIfExists('soplados_inventories');
    }
};
