<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Configuration;
use Carbon\Carbon;

class HeaderComposer
{
    public function compose(View $view)
    {
        $config = Configuration::first();
        $creditDays = $config ? $config->credit_days : 0;
        $creditPurchaseDays = $config ? $config->credit_purchase_days : 0;

        $noty_sales = collect();
        $noty_purchases = collect();

        if ($creditDays > 0) {
            $noty_sales = Sale::where('type', 'credit')
                ->where('status', 'pending')
                ->where('created_at', '<', Carbon::now()->subDays($creditDays))
                ->with('customer')
                ->orderBy('id', 'asc')
                ->get();
        }

        if ($creditPurchaseDays > 0) {
            $noty_purchases = Purchase::where('type', 'credit')
                ->where('status', 'pending')
                ->where('created_at', '<', Carbon::now()->subDays($creditPurchaseDays))
                ->with('supplier')
                ->orderBy('id', 'asc')
                ->get();
        }

        $view->with('noty_sales', $noty_sales);
        $view->with('noty_purchases', $noty_purchases);
        $view->with('credit_days', $creditDays);
        $view->with('credit_purchase_days', $creditPurchaseDays);

        // Commissions Notifications
        $user = auth()->user();
        $noty_commissions = collect();
        
        if ($user) {
            $canManageCommissions = $user->can('gestionar_comisiones');
            
            $query = Sale::query()
                ->where('is_foreign_sale', true)
                ->where('status', 'paid')
                ->where('commission_status', '!=', 'paid')
                ->where(function($q) {
                    $q->where('final_commission_amount', '>', 0)
                      ->orWhere('commission_status', 'pending_calculation')
                      ->orWhereNull('final_commission_amount');
                });

            if (!$canManageCommissions) {
                $query->whereHas('customer', function($q) use ($user) {
                    $q->where('seller_id', $user->id);
                });
            }

            $noty_commissions = $query->orderBy('created_at', 'desc')->get();
        }

        $view->with('noty_commissions', $noty_commissions);

        // Calculate Totals
        $total_receivables = $noty_sales->sum(function($sale) {
            return $sale->debt;
        });

        $total_commissions = $noty_commissions->sum('final_commission_amount');

        $total_payables = $noty_purchases->sum(function($purchase) {
            return $purchase->debt;
        });

        $view->with('total_receivables', $total_receivables);
        $view->with('total_commissions', $total_commissions);
        $view->with('total_payables', $total_payables);

        // Check for Updates (Cached for 12 hours)
        $updateAvailable = \Illuminate\Support\Facades\Cache::remember('system_update_available', 43200, function () {
            try {
                $updater = new \App\Services\UpdateService();
                $result = $updater->checkUpdate();
                return $result['has_update'] ? $result['new_version'] : false;
            } catch (\Exception $e) {
                return false;
            }
        });

        $view->with('updateAvailable', $updateAvailable);
    }
}
