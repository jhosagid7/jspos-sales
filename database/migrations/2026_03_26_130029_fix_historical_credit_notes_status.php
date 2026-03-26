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
        // Fix historical sales that were left as pending but should be paid due to Credit Notes / Returns
        $sales = \App\Models\Sale::whereIn('status', ['pending', 'credit'])->where('type', 'credit')->get();
        
        foreach ($sales as $sale) {
            $sale->checkSettlement();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
