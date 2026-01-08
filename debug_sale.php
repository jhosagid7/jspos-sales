<?php

use App\Models\Sale;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sale = Sale::where('invoice_number', 'F00000004')->with('payments')->first();

if (!$sale) {
    echo "Sale F00000004 not found.\n";
    // Try ID 4
    $sale = Sale::find(4);
    if ($sale) {
        echo "Found Sale ID 4. Invoice: " . $sale->invoice_number . "\n";
    } else {
        echo "Sale ID 4 not found either.\n";
        exit;
    }
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
    echo " - Amount: " . $payment->amount . " " . $payment->currency . " (Rate: " . $payment->exchange_rate . ")\n";
    $totalPaid += $payment->amount; // This is wrong if mixed currencies, but just for display
    
    $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
    $totalPaidUSD += $payment->amount / $rate;
}

echo "Calculated Total Paid USD: " . $totalPaidUSD . "\n";
echo "Debt USD: " . ($sale->total_usd - $totalPaidUSD) . "\n";

if ($totalPaidUSD >= ($sale->total_usd - 0.01)) {
    echo "VERDICT: Should be PAID.\n";
} else {
    echo "VERDICT: Still PENDING.\n";
}
