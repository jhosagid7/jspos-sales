<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Sale;
use App\Models\Order;
use App\Models\Customer;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Correct Sales
        $sales = Sale::where('applied_freight_percent', 0)
            ->where('applied_commission_percent', '>', 0)
            ->get();

        foreach ($sales as $s) {
            $c = Customer::find($s->customer_id);
            $customerConfig = $c ? $c->latestCustomerConfig : null;
            $seller = ($c && $c->seller) ? $c->seller : $s->user;
            $sellerConfig = $s->sellerConfig ?? ($seller ? $seller->latestSellerConfig : null);
            
            $freightPercent = 0;
            if ($customerConfig && floatval($customerConfig->freight_percent) > 0) {
                $freightPercent = floatval($customerConfig->freight_percent);
            } elseif ($sellerConfig && floatval($sellerConfig->freight_percent) > 0) {
                $freightPercent = floatval($sellerConfig->freight_percent);
            }
            
            if ($freightPercent > 0) {
                $base = floatval($s->base_amount);
                $comm = floatval($s->commission_amount);
                $diffPercent = floatval($s->applied_exchange_diff_percent);
                
                $freightAmt = $base * ($freightPercent / 100);
                
                // Recalculate exchange diff (Sequential formula)
                // (Base + Comm + Freight) * Diff%
                $diffAmt = ($base + $comm + $freightAmt) * ($diffPercent / 100);
                
                $s->update([
                    'applied_freight_percent' => $freightPercent,
                    'freight_amount' => round($freightAmt, 4),
                    'exchange_diff_amount' => round($diffAmt, 4),
                ]);
                
                // Recalculate freight for each sale detail
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

        // 2. Correct Orders
        $orders = Order::where('apply_freight', false)
            ->where('apply_commissions', true)
            ->get();

        foreach ($orders as $o) {
            $c = Customer::find($o->customer_id);
            $customerConfig = $c ? $c->latestCustomerConfig : null;
            $seller = ($c && $c->seller) ? $c->seller : $o->user;
            $sellerConfig = $seller ? $seller->latestSellerConfig : null;
            
            $freightPercent = 0;
            if ($customerConfig && floatval($customerConfig->freight_percent) > 0) {
                $freightPercent = floatval($customerConfig->freight_percent);
            } elseif ($sellerConfig && floatval($sellerConfig->freight_percent) > 0) {
                $freightPercent = floatval($sellerConfig->freight_percent);
            }
            
            if ($freightPercent > 0) {
                $base = floatval($o->base_amount);
                $comm = floatval($o->commission_amount);
                $diffPercent = $o->resolved_exchange_diff_percent;
                
                $freightAmt = $base * ($freightPercent / 100);
                $diffAmt = ($base + $comm + $freightAmt) * ($diffPercent / 100);
                
                $o->update([
                    'apply_freight' => true,
                    'freight_amount' => round($freightAmt, 4),
                    'exchange_diff_amount' => round($diffAmt, 4),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback required for data corrections
    }
};
