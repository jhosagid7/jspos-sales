<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CargoDetail;

$details = CargoDetail::where('cargo_id', 240)->get();

echo "Cargo details for cargo #240:\n";
foreach ($details as $detail) {
    echo "ID: " . $detail->id . "\n";
    echo "Product ID: " . $detail->product_id . "\n";
    echo "Quantity: " . $detail->quantity . "\n";
    echo "Cost: " . $detail->cost . "\n";
    echo "Items JSON: " . var_export($detail->items_json, true) . "\n";
    echo "---------------------------------\n";
}
