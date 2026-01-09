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

    public function mount()
    {
        $this->fetchDashboardData();
    }

    public function fetchDashboardData()
    {
        // KPIs
        $this->totalSalesToday = \App\Models\Sale::whereDate('created_at', \Carbon\Carbon::today())->sum('total');
        $this->totalSalesMonth = \App\Models\Sale::whereMonth('created_at', \Carbon\Carbon::now()->month)->sum('total');
        $this->totalPurchasesMonth = \App\Models\Purchase::whereMonth('created_at', \Carbon\Carbon::now()->month)->sum('total');
        
        // Receivables
        $sales = \App\Models\Sale::where('status', '!=', 'paid')->get();
        $this->totalReceivables = $sales->sum(function($sale) {
            $paid = $sale->cash + $sale->payments->sum('amount');
            return max(0, $sale->total - $paid);
        });

        // Recent Sales
        $this->recentSales = \App\Models\Sale::with('customer')->latest()->take(10)->get();

        // Top Products
        $this->topProducts = \App\Models\SaleDetail::select('product_id', \Illuminate\Support\Facades\DB::raw('sum(quantity) as total_qty'))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->with('product')
            ->get();

        // Low Stock
        $this->lowStockProducts = \App\Models\Product::whereColumn('stock_qty', '<=', 'low_stock')
            ->where('manage_stock', 1)
            ->take(10)
            ->get();

        // --- Enhanced Data ---

        // Pending Commissions
        $user = auth()->user();
        $canManageCommissions = $user->can('gestionar_comisiones');
        
        $commissionsQuery = \App\Models\Sale::query()
            ->where('is_foreign_sale', true)
            ->where('status', 'paid')
            ->where('commission_status', '!=', 'paid')
            ->where(function($q) {
                $q->where('final_commission_amount', '>', 0)
                  ->orWhere('commission_status', 'pending_calculation')
                  ->orWhereNull('final_commission_amount');
            });

        if (!$canManageCommissions) {
            $commissionsQuery->whereHas('customer', function($q) use ($user) {
                $q->where('seller_id', $user->id);
            });
        }

        $this->pendingCommissions = $commissionsQuery->sum('final_commission_amount');

        // Top Suppliers (by Purchase Volume)
        $this->topSuppliers = \App\Models\Purchase::select('supplier_id', \Illuminate\Support\Facades\DB::raw('sum(total) as total_purchased'))
            ->groupBy('supplier_id')
            ->orderByDesc('total_purchased')
            ->take(5)
            ->with('supplier')
            ->get();

        // Charts Data (Last 7 Days)
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $dates[] = \Carbon\Carbon::now()->subDays($i)->format('Y-m-d');
        }

        $cashSalesData = [];
        $creditSalesData = [];
        $profitData = [];

        foreach ($dates as $date) {
            // Sales by Date
            $sales = \App\Models\Sale::whereDate('created_at', $date)->get();
            
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
    }

    public function render()
    {
        return view('livewire.welcome.page');
    }
}
