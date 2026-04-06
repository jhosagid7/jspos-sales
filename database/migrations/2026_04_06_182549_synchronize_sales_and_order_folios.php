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
        // Sincronizar Folios de Ventas
        \App\Models\Sale::chunk(100, function ($sales) {
            foreach ($sales as $sale) {
                $formatted = 'F' . str_pad($sale->id, 8, '0', STR_PAD_LEFT);
                if ($sale->invoice_number !== $formatted) {
                    $sale->invoice_number = $formatted;
                    $sale->save();
                }
            }
        });

        // Sincronizar Números de Orden
        \App\Models\Order::chunk(100, function ($orders) {
            foreach ($orders as $order) {
                $formatted = 'P' . str_pad($order->id, 8, '0', STR_PAD_LEFT);
                if ($order->order_number !== $formatted) {
                    $order->order_number = $formatted;
                    $order->save();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverting data change as it's a permanent standard fix for this version
    }
};
