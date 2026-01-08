<?php

use App\Models\Sale;
use App\Models\User;
use App\Models\Customer;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sale = Sale::latest()->first();

if (!$sale) {
    echo "No sales found.\n";
    exit;
}

echo "Latest Sale ID: " . $sale->id . "\n";
echo "Invoice: " . $sale->invoice_number . "\n";
echo "Status: " . $sale->status . "\n";
echo "Total: " . $sale->total . "\n";
echo "Customer: " . ($sale->customer ? $sale->customer->name : 'None') . "\n";
echo "Seller (User): " . ($sale->user ? $sale->user->name : 'None') . "\n";
echo "Assigned Seller (Customer): " . ($sale->customer && $sale->customer->seller ? $sale->customer->seller->name : 'None') . "\n";

echo "Commission Amount: " . $sale->final_commission_amount . "\n";
echo "Commission Status: " . $sale->commission_status . "\n";
echo "Is Foreign Sale: " . ($sale->is_foreign_sale ? 'Yes' : 'No') . "\n";

// Check if CommissionService would calculate something
echo "\n--- Simulation ---\n";
\App\Services\CommissionService::calculateCommission($sale);
$sale->refresh();
echo "Re-calculated Commission Amount: " . $sale->final_commission_amount . "\n";
echo "Re-calculated Commission Status: " . $sale->commission_status . "\n";
