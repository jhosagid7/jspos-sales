<?php

namespace App\Services;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CommissionService
{
    public static function calculateCommission(Sale $sale)
    {
        // Only calculate for foreign sales that are fully paid
        if (!$sale->is_foreign_sale) {
            return "OMITIDO: No es venta foránea";
        }
        
        if ($sale->status !== 'paid') {
            return "OMITIDO: La venta no está PAGADA (Estado: {$sale->status})";
        }

        // Determine the last payment date
        $lastPaymentDate = $sale->created_at;
        if ($sale->payments->count() > 0) {
            $lastPaymentDate = $sale->payments->sortByDesc('created_at')->first()->created_at;
        }

        $daysToPay = $sale->created_at->diffInDays($lastPaymentDate);
        
        // Get the agreed commission percent from the snapshot
        $commissionPercent = $sale->applied_commission_percent ?? 0;
        
        if ($commissionPercent <= 0) {
             return "OMITIDO: Porcentaje de comisión es 0%";
        }

        $finalCommissionPercent = $commissionPercent;

        // Apply Penalty Logic
        // 0-15 days: 100% (No penalty)
        // 16-30 days: 50%
        // >30 days: 0%
        if ($daysToPay > 30) {
            $finalCommissionPercent = 0;
        } elseif ($daysToPay > 15) {
            $finalCommissionPercent = $commissionPercent / 2;
        }

        // Calculate Base Amount
        // The total stored in sale includes surcharges. We need to reverse it to get the base.
        // Formula: Final = Base * (1 + Comm% + Freight% + Diff%)
        $totalSurchargeFactor = 1 + (($sale->applied_commission_percent + $sale->applied_freight_percent + $sale->applied_exchange_diff_percent) / 100);
        
        // Avoid division by zero
        if ($totalSurchargeFactor == 0) $totalSurchargeFactor = 1;
        
        $baseAmount = $sale->total / $totalSurchargeFactor;
        
        // Calculate Final Commission Amount
        $finalCommissionAmount = ($baseAmount * $finalCommissionPercent) / 100;

        // Update Sale
        $sale->update([
            'final_commission_amount' => round($finalCommissionAmount, 2),
            'commission_status' => $finalCommissionAmount > 0 ? 'pending_payment' : 'cancelled',
        ]);

        return "CALCULADO: Monto $" . number_format($finalCommissionAmount, 2) . " (Días: $daysToPay)";
    }
}
