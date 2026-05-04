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
        // Read persisted seller commission config from the sale itself
        $threshold1 = $sale->seller_tier_1_days;
        $percentage1 = $sale->seller_tier_1_percent;
        $threshold2 = $sale->seller_tier_2_days;
        $percentage2 = $sale->seller_tier_2_percent;

        // Fallback: If for some reason old sales don't have tiers, use applied_commission_percent
        if (is_null($threshold1) || is_null($percentage1)) {
             $percentage1 = $sale->applied_commission_percent ?? 0;
             $threshold1 = 9999; // Essentially no time limit
        }

        // Calculate Start Date: delivered_at if available, otherwise created_at + 1 day (grace period)
        $startDate = $sale->delivered_at 
            ? Carbon::parse($sale->delivered_at) 
            : Carbon::parse($sale->created_at)->addDay();

        $reference = $referenceDate ? Carbon::parse($referenceDate) : now();
        $daysElapsed = $startDate->diffInDays($reference);

        // Calculate Effective Sale Total (deducting any returns)
        $sale->loadMissing('returns');
        $totalReturned = $sale->returns ? $sale->returns->sum('total_returned') : 0;
        $effectiveSaleTotal = max(0, $sale->total - $totalReturned);

        // Calculate Base Amount (Reverse Surcharges from effective total)
        $totalSurchargePercent = ($sale->applied_commission_percent ?? 0) + 
                                 ($sale->applied_freight_percent ?? 0) + 
                                 ($sale->applied_exchange_diff_percent ?? 0);
        
        $baseAmount = $effectiveSaleTotal;
        if ($totalSurchargePercent > 0) {
            $baseAmount = $effectiveSaleTotal / (1 + ($totalSurchargePercent / 100));
        }

        // Determine the achieved tier percentage based on days elapsed
        $tierPercentage = 0;

        // Note: percentage1 acts as the maximum/base effort tier
        if ($daysElapsed <= $threshold1) {
            $tierPercentage = $percentage1;
        } elseif (!is_null($threshold2) && $daysElapsed <= $threshold2) {
            $tierPercentage = $percentage2;
        } elseif (!is_null($percentage2) && is_null($threshold2)) {
            // If it has a second percentage but no threshold logic (meaning "anything after tier 1")
            $tierPercentage = $percentage2;
        } else {
            // Exceeded all thresholds (or no tier 2)
            $tierPercentage = $percentage2 ?? 0;
        }

        // Calculate the proportion/ratio of the seller's effort
        $ratio = 1;
        if ($percentage1 > 0) {
            $ratio = $tierPercentage / $percentage1;
        } else if ($percentage1 == 0 && $tierPercentage == 0) {
            $ratio = 1;
        } else {
            $ratio = 0;
        }

        // Apply ratio to the sale's actual markup commission
        $saleMarkup = $sale->applied_commission_percent ?? 0;
        $finalPercentage = $saleMarkup * $ratio;

        // Calculate Commission Amount based on the scaled percentage
        $commissionAmount = ($baseAmount * $finalPercentage) / 100;

        // Save to Sale
        $sale->final_commission_amount = $commissionAmount;
        $sale->commission_status = 'pending_payment'; // Ready to be paid
        $sale->save();

        return $finalPercentage;
    }
}
