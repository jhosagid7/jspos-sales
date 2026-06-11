<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Customer;
use App\Models\CustomerConfig;
use App\Models\Sale;
use App\Models\SaleDetail;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ==========================================
        // FASE 1: Migrar Configuraciones de Vendedor a Clientes
        // ==========================================
        $customers = Customer::all();
        foreach ($customers as $c) {
            // Si el cliente no tiene una configuración explícita
            if (!$c->latestCustomerConfig) {
                $seller = $c->seller;
                $sellerConfig = $seller ? $seller->latestSellerConfig : null;
                
                // Si el vendedor asignado tiene configuración comercial
                if ($sellerConfig) {
                    CustomerConfig::create([
                        'customer_id' => $c->id,
                        'commission_percent' => floatval($sellerConfig->commission_percent),
                        'freight_percent' => floatval($sellerConfig->freight_percent),
                        'exchange_diff_percent' => floatval($sellerConfig->exchange_diff_percent),
                        'current_batch' => $sellerConfig->current_batch ?? '1',
                        'agreement' => $sellerConfig->agreement ?? '',
                    ]);
                }
            }
        }

        // ==========================================
        // FASE 2: Corrección Dinámica de Ventas Históricas
        // ==========================================
        // Buscamos todas las ventas con applied_freight_percent = 0
        $sales = Sale::where('applied_freight_percent', 0)->get();

        foreach ($sales as $s) {
            $c = Customer::find($s->customer_id);
            $customerConfig = $c ? $c->latestCustomerConfig : null;
            
            // Si el cliente tiene flete configurado > 0
            $freightPercent = $customerConfig ? floatval($customerConfig->freight_percent) : 0;
            if ($freightPercent <= 0) {
                continue;
            }

            $base = floatval($s->base_amount);
            $commPercent = floatval($s->applied_commission_percent);
            $diffPercent = floatval($s->applied_exchange_diff_percent);
            $actualTotal = floatval($s->total_usd);

            $freightAmt = $base * ($freightPercent / 100);
            $commAmt = $base * ($commPercent / 100);

            // 1. Total Aditivo esperado con flete
            $totalAdditive = $base * (1 + ($commPercent + $freightPercent + $diffPercent) / 100);

            // 2. Total Secuencial esperado con flete
            $totalSequential = ($base + $commAmt + $freightAmt) * (1 + $diffPercent / 100);

            $matchesAdditive = abs($actualTotal - $totalAdditive) <= 0.05;
            $matchesSequential = abs($actualTotal - $totalSequential) <= 0.05;

            // Si el total de la factura coincide con los cálculos incluyendo flete
            if ($matchesAdditive || $matchesSequential) {
                // Recalcular diferencial secuencial si corresponde
                $diffAmt = $s->exchange_diff_amount;
                if ($matchesSequential && $diffPercent > 0) {
                    $diffAmt = ($base + $commAmt + $freightAmt) * ($diffPercent / 100);
                }

                // Actualizar cabecera de la venta
                $s->update([
                    'applied_freight_percent' => $freightPercent,
                    'freight_amount' => round($freightAmt, 4),
                    'exchange_diff_amount' => round($diffAmt, 4),
                ]);

                // Recalcular el flete para cada artículo detallado de la venta
                foreach ($s->details as $d) {
                    $product = $d->product;
                    $freightAmount = 0;
                    if ($product) {
                        $qty = floatval($d->quantity);
                        $basePrice = floatval($d->regular_price);
                        
                        if ($product->freight_type == 'personalized' || $product->freight_type == 'fixed') {
                            $freightAmount = $product->freight_value * $qty;
                        } elseif ($product->freight_type == 'percentage') {
                            $freightAmount = ($basePrice * $product->freight_value / 100) * $qty;
                        } else {
                            $freightAmount = ($basePrice * $freightPercent / 100) * $qty;
                        }
                    } else {
                        $freightAmount = (floatval($d->regular_price) * floatval($d->quantity)) * ($freightPercent / 100);
                    }
                    
                    $d->update([
                        'freight_amount' => round($freightAmount, 4)
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback para cambios históricos de datos
    }
};
