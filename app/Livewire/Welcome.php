<?php

namespace App\Livewire;

use Livewire\Component;
//use Spatie\Permission\Models\Role;
//use Spatie\Permission\Models\Permission;

class Welcome extends Component
{
    public $totalSalesToday = 0;
    public $totalSalesMonth = 0;
    public $totalPurchasesMonth = 0;
    public $totalReceivables = 0;
    
    public $recentSales = [];
    public $topProducts = [];
    public $lowStockProducts = [];

    // Enhanced Dashboard Properties
    public $salesChartData = [];
    public $profitChartData = [];
    public $topSuppliers = [];
    public $pendingCommissions = 0;
    public $topSellersChartData = [];

    public function mount()
    {
        if (auth()->user()->hasRole('Driver')) {
            return redirect()->route('driver.dashboard');
        }
        $this->fetchDashboardData();
    }

    private function getSalesQuery()
    {
        $query = \App\Models\Sale::query();
        
        if (!auth()->user()->can('sales.view_all') && auth()->user()->can('sales.view_own')) {
            $query->where('user_id', auth()->id());
        }
        
        return $query;
    }

    public function fetchDashboardData()
    {
        // KPIs
        $this->totalSalesToday = $this->getSalesQuery()->whereDate('created_at', \Carbon\Carbon::today())->sum('total');
        $this->totalSalesMonth = $this->getSalesQuery()->whereMonth('created_at', \Carbon\Carbon::now()->month)->sum('total');
        if (auth()->user()->can('purchases.index')) {
            $this->totalPurchasesMonth = \App\Models\Purchase::whereMonth('created_at', \Carbon\Carbon::now()->month)->sum('total');
        }
        
        // Receivables
        $sales = $this->getSalesQuery()->where('status', '!=', 'paid')->get();
        $this->totalReceivables = $sales->sum(function($sale) {
            $paid = $sale->cash + $sale->payments->sum('amount');
            return max(0, $sale->total - $paid);
        });

        // Recent Sales
        $this->recentSales = $this->getSalesQuery()->with('customer')->latest()->take(10)->get();

        // Top Products
        // Top Products (This Month)
        // Note: For complex joins, we need to apply the condition on the sales table
        $topProductsQuery = \App\Models\SaleDetail::query()
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->select('sale_details.product_id', \Illuminate\Support\Facades\DB::raw('sum(sale_details.quantity) as total_qty'))
            ->where('sales.status', 'paid')
            ->whereMonth('sales.created_at', \Carbon\Carbon::now()->month);

        if (!auth()->user()->can('sales.view_all') && auth()->user()->can('sales.view_own')) {
            $topProductsQuery->where('sales.user_id', auth()->id());
        }

        $this->topProducts = $topProductsQuery->groupBy('sale_details.product_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->with('product.images')
            ->get();

        // Low Stock
        $this->lowStockProducts = \App\Models\Product::select('id', 'name', 'stock_qty', 'low_stock', 'manage_stock')
            ->whereColumn('stock_qty', '<=', 'low_stock')
            ->where('manage_stock', 1)
            ->take(10)
            ->get();

        // --- Enhanced Data ---

        // Pending Commissions
        $user = auth()->user();
        $canManageCommissions = $user->can('gestionar_comisiones'); // Consider changing this to granular if exists
        
        $commissionsQuery = $this->getSalesQuery() // Reused base query logic
            ->where('is_foreign_sale', true)
            ->where('status', 'paid')
            ->where('commission_status', '!=', 'paid')
            ->where(function($q) {
                $q->where('final_commission_amount', '>', 0)
                  ->orWhere('commission_status', 'pending_calculation')
                  ->orWhereNull('final_commission_amount');
            });

        // Note: Logic for commissions might be specific to Seller ID on Customer, but getSalesQuery scopes to Sale User ID
        // If "view_own" implies seeing commissions for *their* sales, getSalesQuery handles it.
        // If logic is strictly about Customer ownership regardless of who sold, we might need adjustments.
        // Keeping original specific logic for now if it differs, but merging overlap.
        
        // Original logic:
        /*
        if (!$canManageCommissions) {
            $commissionsQuery->whereHas('customer', function($q) use ($user) {
                $q->where('seller_id', $user->id);
            });
        }
        */
        // If we use getSalesQuery, we are filtering by sales.user_id = auth->id. 
        // This usually aligns with the seller, but "commission" relates to the customer's seller.
        // Let's stick to the original commission logic for safety, but apply the filtered sales if appropriate.
        // Actually, let's leave commission logic as is for now to avoid breaking specific commission rules, 
        // as "view_own" for dashboard implies sales data visibility.

        if (auth()->user()->can('commissions.view_all') || auth()->user()->can('commissions.view_own')) {
            $commissionsQuery = \App\Models\Sale::query() 
                ->where('is_foreign_sale', true)
                ->where('status', 'paid')
                ->whereNotIn('status', ['returned', 'voided', 'cancelled', 'anulated'])
                ->where('commission_status', '!=', 'paid')
                ->where(function($q) {
                    $q->where('final_commission_amount', '>', 0)
                      ->orWhere('commission_status', 'pending_calculation')
                      ->orWhereNull('final_commission_amount');
                });

            if (!auth()->user()->can('commissions.view_all')) {
                 // Force filter by view_own logic
                 $commissionsQuery->whereHas('customer', function($q) use ($user) {
                    $q->where('seller_id', $user->id);
                });
            }

            $this->pendingCommissions = $commissionsQuery->sum('final_commission_amount');
        } else {
            $this->pendingCommissions = 0;
        }

        // Top Suppliers (by Purchase Volume)
        if (auth()->user()->can('purchases.index')) {
            $this->topSuppliers = \App\Models\Purchase::select('supplier_id', \Illuminate\Support\Facades\DB::raw('sum(total) as total_purchased'))
                ->groupBy('supplier_id')
                ->orderByDesc('total_purchased')
                ->take(5)
                ->with('supplier')
                ->get();
        }

        // Charts Data (Last 7 Days)
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $dates[] = \Carbon\Carbon::now()->subDays($i)->format('Y-m-d');
        }

        $cashSalesData = [];
        $creditSalesData = [];
        $profitData = [];

        foreach ($dates as $date) {
            // Sales by Date - Apply getSalesQuery
            $sales = $this->getSalesQuery()->whereDate('created_at', $date)->get();
            
            // Cash (Paid) vs Credit (Pending)
            $cashSalesData[] = $sales->where('status', 'paid')->sum('total');
            $creditSalesData[] = $sales->where('status', 'pending')->sum('total');

            // Profit (Simplified: Total - (Cost * Qty))
            $dailyProfit = 0;
            foreach($sales as $sale) {
                foreach($sale->details as $detail) {
                    $cost = $detail->product->cost ?? 0;
                    $dailyProfit += ($detail->price * $detail->quantity) - ($cost * $detail->quantity);
                }
            }
            $profitData[] = $dailyProfit;
        }

        $this->salesChartData = [
            'labels' => $dates,
            'cash' => $cashSalesData,
            'credit' => $creditSalesData
        ];

        $this->profitChartData = [
            'labels' => $dates,
            'data' => $profitData
        ];

        // Top Sellers by Profit (This Month)
        // Only show if user has permission to see all sales, otherwise they only see themselves (which is trivial but correct)
        
        $topSellersQuery = \App\Models\SaleDetail::query()
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->join('users', 'customers.seller_id', '=', 'users.id')
            ->join('model_has_roles', function($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                     ->where('model_has_roles.model_type', 'App\Models\User');
            })
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select(
                'users.name as seller_name',
                \Illuminate\Support\Facades\DB::raw('SUM((sale_details.sale_price - COALESCE(products.cost, 0)) * sale_details.quantity) as total_profit')
            )
            ->where('sales.status', 'paid')
            ->whereMonth('sales.created_at', \Carbon\Carbon::now()->month)
            ->where('roles.name', 'Vendedor');

        if (!auth()->user()->can('sales.view_all') && auth()->user()->can('sales.view_own')) {
             // For consistency, if they can only see their own sales, they probably shouldn't see other sellers' stats
             // But the original query joins on `customers.seller_id`. 
             // If we strict it to `sales.user_id`, we align with "view_own" sales.
             $topSellersQuery->where('sales.user_id', auth()->id());
        }

        $this->topSellers = $topSellersQuery->groupBy('users.name')
            ->orderByDesc('total_profit')
            ->take(5)
            ->get();

        $this->topSellersChartData = $this->topSellers->map(function($seller) {
            return [
                'name' => $seller->seller_name,
                'y' => (float) $seller->total_profit
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.welcome.page');
    }
}
