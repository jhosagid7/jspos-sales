<?php

use App\Models\Sale;
use App\Models\Currency;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sale = Sale::find(4);

if (!$sale) {
    echo "Sale ID 4 not found.\n";
    exit;
}

echo "Current State:\n";
echo "Total: " . $sale->total . "\n";
echo "Total USD: " . $sale->total_usd . "\n";
echo "Primary Currency: " . $sale->primary_currency_code . "\n";
echo "Primary Rate: " . $sale->primary_exchange_rate . "\n";

// Fix logic
if ($sale->total > 0 && $sale->total_usd == 0) {
    $rate = $sale->primary_exchange_rate;
    
    // If rate is invalid (e.g. 1 for COP), try to get current system rate
    if ($rate <= 1 && $sale->primary_currency_code != 'USD') {
        $currency = Currency::where('code', $sale->primary_currency_code)->first();
        if ($currency) {
            $rate = $currency->exchange_rate;
            echo "Using current system rate for {$currency->code}: $rate\n";
            
            // Update sale rate too if it was wrong
            $sale->primary_exchange_rate = $rate;
        }
    }
    
    if ($rate > 0) {
        $sale->total_usd = $sale->total / $rate;
        $sale->save();
        echo "Updated Total USD to: " . $sale->total_usd . "\n";
    } else {
        echo "Could not determine valid exchange rate.\n";
    }
} else {
    echo "No update needed or Total is 0.\n";
}
