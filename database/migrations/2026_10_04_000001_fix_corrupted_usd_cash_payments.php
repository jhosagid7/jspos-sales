<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // Buscar pagos en efectivo guardados como USD pero pertenecientes a ventas en moneda local (VED/COP)
            $corruptedPayments = DB::table('sale_payment_details as spd')
                ->join('sales as s', 's.id', '=', 'spd.sale_id')
                ->where('spd.currency_code', 'USD')
                ->where('spd.exchange_rate', 1)
                ->whereIn('s.primary_currency_code', ['VED', 'VES', 'COP'])
                ->where('spd.amount', '>', 100)
                ->select(
                    'spd.id as payment_id',
                    'spd.amount as payment_amount',
                    's.id as sale_id',
                    's.total as sale_total',
                    's.primary_currency_code as sale_currency',
                    's.primary_exchange_rate as sale_rate'
                )
                ->get();

            foreach ($corruptedPayments as $record) {
                $rate = $record->sale_rate > 0 ? $record->sale_rate : 880;
                $amountInPrimary = round($record->payment_amount / $rate, 2);
                $changeInLocal = max(0, round($record->payment_amount - $record->sale_total, 2));

                // 1. Corregir sale_payment_details
                DB::table('sale_payment_details')
                    ->where('id', $record->payment_id)
                    ->update([
                        'currency_code' => $record->sale_currency,
                        'exchange_rate' => $rate,
                        'amount_in_primary_currency' => $amountInPrimary,
                        'updated_at' => now(),
                    ]);

                // 2. Corregir cabecera de la venta (cash y change)
                DB::table('sales')
                    ->where('id', $record->sale_id)
                    ->update([
                        'cash' => $amountInPrimary,
                        'change' => $changeInLocal,
                        'updated_at' => now(),
                    ]);

                Log::info("Saneamiento automático aplicado a Venta #{$record->sale_id} (Pago #{$record->payment_id}): Monto {$record->payment_amount} {$record->sale_currency} convertido a {$amountInPrimary} USD.");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructivo: los datos corregidos no se revierten
    }
};
