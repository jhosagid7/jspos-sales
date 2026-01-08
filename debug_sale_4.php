<?php

use App\Models\Sale;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sale = Sale::find(4);

if (!$sale) {
    echo "Sale ID 4 not found.\n";
    exit;
}

echo "Sale ID: " . $sale->id . "\n";
echo "Invoice: " . $sale->invoice_number . "\n";
echo "Status: " . $sale->status . "\n";
echo "Total: " . $sale->total . "\n";
echo "Total USD: " . $sale->total_usd . "\n";

echo "Payments:\n";
$totalPaid = 0;
$totalPaidUSD = 0;
foreach ($sale->payments as $payment) {
    echo " - ID: " . $payment->id . ", Amount: " . $payment->amount . " " . $payment->currency . " (Rate: " . $payment->exchange_rate . ")\n";
    $totalPaid += $payment->amount; 
    
    $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
    $amountUSD = $payment->amount / $rate;
    echo "   -> USD: " . $amountUSD . "\n";
    $totalPaidUSD += $amountUSD;
}

echo "Calculated Total Paid USD: " . $totalPaidUSD . "\n";
echo "Debt USD: " . ($sale->total_usd - $totalPaidUSD) . "\n";
