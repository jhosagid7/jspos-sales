<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Configuration;
use Carbon\Carbon;

class HeaderComposer
{
    private function cleanString($string)
    {
        if (is_null($string)) return '';

        // Force UTF-8 first
        $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);
        
        // Remove control characters
        $cleaned = preg_replace('/[\x00-\x1F\x7F]/u', '', $string);
        $string = $cleaned ?? $string;

        // JSON Failsafe
        if (json_encode($string) === false) {
            return "INVALID_ENCODING";
        }
        
        return $string;
    }

    public function compose(View $view)
    {
        $config = Configuration::first();
        $creditDays = $config ? $config->credit_days : 0;
        $creditPurchaseDays = $config ? $config->credit_purchase_days : 0;

        $noty_sales = collect();
        $noty_purchases = collect();
        $user = auth()->user();

        // Overdue Sales Notifications
        $salesQuery = Sale::where('sales.type', 'credit')
            ->where('sales.status', 'pending')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->whereNull('customers.deleted_at')
            ->whereRaw("
                DATEDIFF(
                    NOW(), 
                    DATE_ADD(
                        COALESCE(sales.delivered_at, sales.created_at), 
                        INTERVAL COALESCE(
                            NULLIF(sales.credit_days, 0), 
                            NULLIF(customers.credit_days, 0), 
                            {$creditDays}
                        ) DAY
                    )
                ) >= 0
            ")
            ->select('sales.*')
            ->with(['customer', 'payments', 'paymentDetails', 'returns'])
            ->orderBy('sales.id', 'asc');

        if ($user && !$user->can('sales.view_all') && !$user->can('reports.accounts_receivable.view_all')) {
            $salesQuery->where('customers.seller_id', $user->id);
        }

        $noty_sales = $salesQuery->get()
            ->filter(function($sale) {
                return $sale->debt > 0;
            })
            ->transform(function($sale) {
                if ($sale->customer) {
                    $sale->customer->name = $this->cleanString($sale->customer->name);
                }
                return $sale;
            });

        // Overdue Purchases Notifications
        $purchasesQuery = Purchase::where('purchases.type', 'credit')
            ->where('purchases.status', 'pending')
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->whereNull('suppliers.deleted_at')
            ->whereRaw("
                DATEDIFF(
                    NOW(), 
                    DATE_ADD(
                        purchases.created_at, 
                        INTERVAL {$creditPurchaseDays} DAY
                    )
                ) >= 0
            ")
            ->select('purchases.*')
            ->with(['supplier', 'payments'])
            ->orderBy('purchases.id', 'asc');

        $noty_purchases = $purchasesQuery->get()
            ->filter(function($purchase) {
                return $purchase->debt > 0;
            })
            ->transform(function($purchase) {
                if ($purchase->supplier) {
                    $purchase->supplier->name = $this->cleanString($purchase->supplier->name);
                }
                return $purchase;
            });

        $view->with('noty_sales', $noty_sales);
        $view->with('noty_purchases', $noty_purchases);
        $view->with('credit_days', $creditDays);
        $view->with('credit_purchase_days', $creditPurchaseDays);

        // Commissions Notifications
        $user = auth()->user();
        $noty_commissions = collect();
        
        if ($user && ($user->can('commissions.view_all') || $user->can('commissions.view_own'))) {
            $canViewAll = $user->can('commissions.view_all');
            
            $query = Sale::query()
                ->where('is_foreign_sale', true)
                ->where('status', 'paid')
                ->whereNotIn('status', ['returned', 'voided', 'cancelled', 'anulated'])
                ->where('commission_status', '!=', 'paid')
                ->where(function($q) {
                    $q->where('final_commission_amount', '>', 0)
                      ->orWhere('commission_status', 'pending_calculation')
                      ->orWhereNull('final_commission_amount');
                });

            if (!$canViewAll) {
                $query->whereHas('customer', function($q) use ($user) {
                    $q->where('seller_id', $user->id);
                });
            }

            $noty_commissions = $query->orderBy('created_at', 'desc')->get()
                ->transform(function($sale) {
                    if ($sale->customer) {
                        $sale->customer->name = $this->cleanString($sale->customer->name);
                    }
                    return $sale;
                });
        }

        // Sanitize user name
        if ($user) {
            $user->name = $this->cleanString($user->name);
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

        // Online Devices
        $online_devices_count = \App\Models\DeviceAuthorization::where('last_accessed_at', '>=', Carbon::now()->subMinutes(10))->count();
        $view->with('online_devices_count', $online_devices_count);
    }
}
