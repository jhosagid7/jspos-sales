<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Cargo;

$cargos = Cargo::with('details')->whereNotNull('production_id')->get();

echo "Total cargos with production_id: " . $cargos->count() . "\n\n";
foreach ($cargos as $cargo) {
    echo "Cargo ID: " . $cargo->id . "\n";
    echo "Production ID: " . $cargo->production_id . "\n";
    echo "Motive: " . $cargo->motive . "\n";
    echo "Comments: " . $cargo->comments . "\n";
    echo "Status: " . $cargo->status . "\n";
    foreach ($cargo->details as $detail) {
        echo "  - Detail ID: " . $detail->id . "\n";
        echo "  - Product ID: " . $detail->product_id . "\n";
        echo "  - Quantity: " . $detail->quantity . "\n";
        echo "  - Items JSON: " . var_export($detail->items_json, true) . "\n";
    }
    echo "=================================\n";
}
