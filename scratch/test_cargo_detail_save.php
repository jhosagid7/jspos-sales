<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CargoDetail;

$metadata = [
    [
        'batch' => null,
        'color' => null,
        'weight' => 23.5,
    ],
    [
        'batch' => null,
        'color' => null,
        'weight' => 20.4,
    ],
    [
        'batch' => null,
        'color' => null,
        'weight' => 21.2,
    ]
];

try {
    $detail = CargoDetail::create([
        'cargo_id'   => 240,
        'product_id' => 211,
        'quantity'   => 65.10,
        'cost'       => 4.30,
        'items_json' => json_encode($metadata)
    ]);
    
    echo "Saved Detail ID: " . $detail->id . "\n";
    echo "Saved Items JSON field: " . var_export($detail->items_json, true) . "\n";
    
    // Fetch from database
    $fetched = CargoDetail::find($detail->id);
    echo "Fetched Items JSON field: " . var_export($fetched->items_json, true) . "\n";
    
    // Clean up
    $fetched->delete();
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
