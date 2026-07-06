<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('year_month', 7); // Format: YYYY-MM
            $table->string('category');      // e.g. 'Nómina', 'Alquiler', 'Servicios', 'Otros'
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 4); // Amount in USD
            $table->timestamps();
 
            // Index for faster monthly aggregation
            $table->index('year_month');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('operational_expenses');
    }
};
