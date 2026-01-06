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
    }
}
