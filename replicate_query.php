<?php

use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate Auth::user() as Jhonny
$user = User::find(1); // Jhonny
Auth::login($user);

$canManage = $user->can('gestionar_comisiones');
echo "User: {$user->name}, Can Manage: " . ($canManage ? 'Yes' : 'No') . "\n";

$dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
$dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
$seller_id = 0;
$status_filter = 'all';

$query = Sale::query()
    ->with(['customer', 'user', 'payments'])
    ->where('is_foreign_sale', true)
    ->where(function($q) {
        $q->where('final_commission_amount', '>', 0)
          ->orWhere('commission_status', 'pending_calculation')
          ->orWhereNull('final_commission_amount');
    });

// Filter by Date Range
$query->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

// Filter by Seller
if ($canManage) {
    if ($seller_id != 0) {
        $query->whereHas('customer', function($q) use ($seller_id) {
            $q->where('seller_id', $seller_id);
        });
    }
} else {
    $query->whereHas('customer', function($q) use ($user) {
        $q->where('seller_id', $user->id);
    });
}

// Filter by Status
if ($status_filter !== 'all') {
    if ($status_filter === 'pending') {
        $query->where('commission_status', '!=', 'paid');
    } else {
        $query->where('commission_status', $status_filter);
    }
}

$count = $query->count();
echo "Query matched $count sales.\n";

$sales = $query->orderBy('created_at', 'desc')->get();
foreach ($sales as $sale) {
    echo "- Sale ID: {$sale->id}, Invoice: {$sale->invoice_number}, Comm: {$sale->final_commission_amount}, Status: {$sale->commission_status}\n";
}
