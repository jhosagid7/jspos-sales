<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Sale;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix historical sales that were left as pending but should be paid due to initial payments
        $sales = Sale::where('type', 'credit')
            ->where('status', 'pending')
            ->get();

        foreach ($sales as $sale) {
            // Run the calculation logic
            $currentTotalPaidUSD = $sale->payments->where('status', 'approved')->sum(function($p) {
                $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                $amountUSD = $p->amount / $rate; 
                $adjustmentUSD = $p->discount_applied ?? 0;
                if ($p->rule_type === 'overdue') {
                    return $amountUSD - $adjustmentUSD;
                } else {
                    return $amountUSD + $adjustmentUSD;
                }
            });
            
            $initialPaidUSD = $sale->paymentDetails->sum(function($detail) {
                $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
                return $detail->amount / $rate;
            });

            $totalReturnsOrig = $sale->returns->where('refund_method', 'debt_reduction')->where('status', 'approved')->sum('total_returned');
            $exchangeRateReturns = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
            $totalReturnsUSD = $totalReturnsOrig / $exchangeRateReturns;
            
            $totalDebitNotesUSD = $sale->debitNotes->where('status', '<>', 'voided')->sum(function($dn) {
                $rate = $dn->exchange_rate > 0 ? $dn->exchange_rate : 1;
                return $dn->amount / $rate;
            });

            $grandTotalPaidUSD = $currentTotalPaidUSD + $initialPaidUSD + $totalReturnsUSD;
            $targetTotalUSD = $sale->total_usd + $totalDebitNotesUSD;

            if ($grandTotalPaidUSD >= ($targetTotalUSD - 0.01)) {
                $sale->checkSettlement();
            }
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
