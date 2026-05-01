<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

$sale = Sale::find(417);
if (!$sale) {
    echo "Sale 417 not found\n";
    exit;
}

echo "Sale ID: " . $sale->id . "\n";
echo "Status: " . $sale->status . "\n";
echo "Total USD: " . $sale->total_usd . "\n";
echo "Total COP: " . $sale->total . "\n";
echo "Primary Exchange Rate: " . $sale->primary_exchange_rate . "\n";

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
echo "Current Total Paid USD: " . $currentTotalPaidUSD . "\n";

$initialPaidUSD = $sale->paymentDetails->sum(function($detail) {
    $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
    return $detail->amount / $rate;
});
echo "Initial Paid USD: " . $initialPaidUSD . "\n";

$totalReturnsOrig = $sale->returns->where('refund_method', 'debt_reduction')->where('status', 'approved')->sum('total_returned');
$exchangeRateReturns = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
$totalReturnsUSD = $totalReturnsOrig / $exchangeRateReturns;
echo "Total Returns USD: " . $totalReturnsUSD . "\n";

$grandTotalPaidUSD = $currentTotalPaidUSD + $initialPaidUSD + $totalReturnsUSD;
echo "Grand Total Paid USD: " . $grandTotalPaidUSD . "\n";
echo "Difference (TotalUSD - GrandTotal): " . ($sale->total_usd - $grandTotalPaidUSD) . "\n";

if ($grandTotalPaidUSD >= ($sale->total_usd - 0.01)) {
    echo "SETTLEMENT CRITERIA MET\n";
} else {
    echo "SETTLEMENT CRITERIA NOT MET\n";
}
