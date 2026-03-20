<?php
use App\Models\Sale;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$lastSale = Sale::latest()->first();
if ($lastSale) {
    echo "ID: " . $lastSale->id . "\n";
    echo "Invoice: " . ($lastSale->invoice_number ?? 'N/A') . "\n";
    echo "Driver ID: " . ($lastSale->driver_id ?? 'NULL') . "\n";
    echo "Created At: " . $lastSale->created_at . "\n";
    echo "Total: " . $lastSale->total_usd . "\n";
} else {
    echo "No sales found\n";
}
