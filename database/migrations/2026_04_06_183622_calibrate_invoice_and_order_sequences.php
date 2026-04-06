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
        $maxSaleId = \App\Models\Sale::max('id') ?? 0;
        $maxOrderId = \App\Models\Order::max('id') ?? 0;

        $config = \App\Models\Configuration::first();
        if ($config) {
            $config->invoice_sequence = $maxSaleId;
            $config->order_sequence = $maxOrderId;
            $config->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverting data calibration
    }
};
