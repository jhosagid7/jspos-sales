<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class CommissionReport extends Component
{
    use WithPagination;

    public $seller_id;
    public $dateFrom, $dateTo;
    public $details = [];

    public function mount()
    {
        $this->dateFrom = Carbon::parse(Carbon::now())->format('Y-m-d');
        $this->dateTo = Carbon::parse(Carbon::now())->format('Y-m-d');
        $this->seller_id = 0;
    }

    public function render()
    {
        $sellers = User::role('Vendedor')->get();
        $sales = [];

        if ($this->seller_id != 0) {
            $query = Sale::with(['customer', 'payments', 'sellerConfig'])
                ->where('is_foreign_sale', true)
                ->whereHas('customer', function($q) {
                    $q->where('seller_id', $this->seller_id);
                })
                ->whereBetween('created_at', [$this->dateFrom . ' 00:00:00', $this->dateTo . ' 23:59:59'])
                ->where('status', 'paid');

            $sales = $query->get()->map(function($sale) {
                // Calculate days to pay
                $lastPaymentDate = $sale->payments->sortByDesc('created_at')->first()->created_at ?? $sale->created_at;
                $daysToPay = $sale->created_at->diffInDays($lastPaymentDate);

                // Commission Logic
                $commissionPercent = $sale->applied_commission_percent ?? 0;
                $penalty = 0;
                $finalCommissionPercent = $commissionPercent;

                if ($daysToPay > 30) {
                    $finalCommissionPercent = 0;
                    $penalty = 100;
                } elseif ($daysToPay > 15) {
                    $finalCommissionPercent = $commissionPercent / 2;
                    $penalty = 50;
                }

                // Calculate Commission Amount
                // Base amount for commission is (Total / (1 + Freight% + Diff% + Comm%)) * Comm% ? 
                // Or is it applied on the Base Price?
                // The stored 'total' includes all surcharges.
                // We need to reverse calculate the base if we want exact precision, or use the stored percentages.
                // Formula: Final = Base + (Base*C) + (Base*F) + (Base*D)
                // Final = Base * (1 + C + F + D)
                // Base = Final / (1 + C + F + D)
                
                $totalSurchargeFactor = 1 + (($sale->applied_commission_percent + $sale->applied_freight_percent + $sale->applied_exchange_diff_percent) / 100);
                $baseAmount = $sale->total / $totalSurchargeFactor;
                
                $commissionAmount = ($baseAmount * $finalCommissionPercent) / 100;

                return [
                    'id' => $sale->id,
                    'date' => $sale->created_at->format('Y-m-d'),
                    'paid_date' => $lastPaymentDate->format('Y-m-d'),
                    'days' => $daysToPay,
                    'customer' => $sale->customer->name,
                    'total' => $sale->total,
                    'base_amount' => $baseAmount,
                    'applied_percent' => $commissionPercent,
                    'penalty' => $penalty,
                    'final_percent' => $finalCommissionPercent,
                    'commission_amount' => $commissionAmount
                ];
            });
        }

        return view('livewire.commission-report', [
            'sellers' => $sellers,
            'sales' => $sales
        ]);
    }
}
