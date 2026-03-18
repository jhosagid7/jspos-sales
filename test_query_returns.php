<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sale = App\Models\Sale::with('returns')->find(107);
if($sale) {
    echo "Total Returns Debt Reduction: " . $sale->returns->where('refund_method', 'debt_reduction')->sum('total_returned') . "\n";
    
    $totalReturnsOrig = $sale->returns->where('refund_method', 'debt_reduction')->sum('total_returned');
    $exchangeRateReturns = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
    $totalReturnsUSD = $totalReturnsOrig / $exchangeRateReturns;
    echo "Total Returns USD: " . $totalReturnsUSD . "\n";
} else {
    echo "No sale found";
}
