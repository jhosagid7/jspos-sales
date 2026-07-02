<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = \App\Models\Product::where('name', 'like', '%ZANAHORIA%')
    ->orWhere('name', 'like', '%BOBINA%')
    ->get();

foreach ($products as $product) {
    echo "=== PRODUCTO: '{$product->name}' ===\n";
    echo "ID: {$product->id}\n";
    echo "SKU: {$product->sku}\n";
    echo "stock_qty: {$product->stock_qty}\n";
    echo "is_variable_quantity: " . var_export($product->is_variable_quantity, true) . "\n";
    
    $whs = \App\Models\ProductWarehouse::where('product_id', $product->id)->get();
    foreach ($whs as $w) {
        $name = \App\Models\Warehouse::find($w->warehouse_id)->name ?? 'Desconocido';
        echo " - Depósito: {$name} | stock_qty: {$w->stock_qty}\n";
    }
}
