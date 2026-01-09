<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Configuration;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CommissionService
{
    public static function calculateCommission(Sale $sale, $referenceDate = null)
    {
        $customer = $sale->customer;
        // Use the customer's assigned seller if available, otherwise the user who made the sale
        $seller = $customer->seller ?? $sale->user; 
        $config = Configuration::first();

        // 1. Check Customer Configuration
        $threshold1 = $customer->customer_commission_1_threshold;
        $percentage1 = $customer->customer_commission_1_percentage;
        $threshold2 = $customer->customer_commission_2_threshold;
        $percentage2 = $customer->customer_commission_2_percentage;

        // 2. Fallback to Seller Configuration
        if (is_null($threshold1) || is_null($percentage1)) {
            $threshold1 = $seller->seller_commission_1_threshold;
            $percentage1 = $seller->seller_commission_1_percentage;
            $threshold2 = $seller->seller_commission_2_threshold;
            $percentage2 = $seller->seller_commission_2_percentage;
        }

        // 3. Fallback to Global Configuration
        if (is_null($threshold1) || is_null($percentage1)) {
            if ($config) {
                $threshold1 = $config->global_commission_1_threshold;
                $percentage1 = $config->global_commission_1_percentage;
                $threshold2 = $config->global_commission_2_threshold;
                $percentage2 = $config->global_commission_2_percentage;
            }
        }

        // Alert if no configuration found
        if (is_null($threshold1) || is_null($percentage1)) {
            Log::warning("No commission configuration found for Sale ID: {$sale->id}");
            return 0;
        }

        // Calculate Days Elapsed
        $reference = $referenceDate ? Carbon::parse($referenceDate) : now();
        $daysElapsed = Carbon::parse($sale->created_at)->diffInDays($reference);

        // Calculate Base Amount (Reverse Surcharges)
        $totalSurchargePercent = ($sale->applied_commission_percent ?? 0) + 
                                 ($sale->applied_freight_percent ?? 0) + 
                                 ($sale->applied_exchange_diff_percent ?? 0);
        
        $baseAmount = $sale->total;
        if ($totalSurchargePercent > 0) {
            $baseAmount = $sale->total / (1 + ($totalSurchargePercent / 100));
        }

        // Apply Logic
        // If payment is within the first threshold (e.g., <= 15 days)
        if ($daysElapsed <= $threshold1) {
            $commissionAmount = ($baseAmount * $percentage1) / 100;
            $sale->final_commission_amount = $commissionAmount;
            $sale->commission_status = 'pending_payment';
            $sale->save();
            return $percentage1;
        }
        
        // If payment is within the second threshold (e.g., <= 22 days)
        if (!is_null($threshold2)) {
            if ($daysElapsed <= $threshold2) {
                $commissionAmount = ($baseAmount * $percentage2) / 100;
                $sale->final_commission_amount = $commissionAmount;
                $sale->commission_status = 'pending_payment';
                $sale->save();
                return $percentage2;
            }
        } elseif (!is_null($percentage2)) {
             $commissionAmount = ($baseAmount * $percentage2) / 100;
             $sale->final_commission_amount = $commissionAmount;
             $sale->commission_status = 'pending_payment';
             $sale->save();
             return $percentage2;
        }

        // If it exceeds all thresholds
        $finalPercentage = $percentage2 ?? 0;

        // Calculate Amount
        $commissionAmount = ($baseAmount * $finalPercentage) / 100;

        // Save to Sale
        $sale->final_commission_amount = $commissionAmount;
        $sale->commission_status = 'pending_payment'; // Ready to be paid
        $sale->save();

        return $finalPercentage;
    }
}
