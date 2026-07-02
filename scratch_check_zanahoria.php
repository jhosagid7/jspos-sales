<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$product = \App\Models\Product::where('name', 'like', '%ZANAHORIA%')->first();

if (!$product) {
    echo "Producto no encontrado.\n";
    exit;
}

echo "=== PRODUCTO: {$product->name} ===\n";
echo "ID: {$product->id}\n";
echo "SKU: {$product->sku}\n";
echo "stock_qty (Stock Actual): {$product->stock_qty}\n";
echo "manage_stock: {$product->manage_stock}\n";
echo "is_variable_quantity (Bobinas): " . var_export($product->is_variable_quantity, true) . "\n";
echo "is_pre_assembled (Kit Preensamblado): " . var_export($product->is_pre_assembled, true) . "\n";
echo "show_in_sales: " . var_export($product->show_in_sales, true) . "\n";

echo "\n--- COMPONENTES ---\n";
foreach ($product->components as $c) {
    echo " - Componente ID: {$c->id} | Nombre: {$c->name} | Qty: {$c->pivot->quantity}\n";
}

echo "\n--- WAREHOUSE DISTRIBUTIONS (ProductWarehouse) ---\n";
$whs = \App\Models\ProductWarehouse::where('product_id', $product->id)->get();
foreach ($whs as $w) {
    $name = \App\Models\Warehouse::find($w->warehouse_id)->name ?? 'Desconocido';
    echo " - Depósito: {$name} | stock_qty: {$w->stock_qty}\n";
}

echo "\n--- ITEMS VARIABLES (ProductItem) (Limit 5) ---\n";
$items = \App\Models\ProductItem::where('product_id', $product->id)->take(5)->get();
foreach ($items as $i) {
    echo " - Item ID: {$i->id} | Cantidad (Peso): {$i->quantity} | Estatus: {$i->status} | Depósito: " . (\App\Models\Warehouse::find($i->warehouse_id)->name ?? 'Desconocido') . "\n";
}

echo "\n--- RESUMEN DE ITEMS DISPONIBLES ---\n";
$availableCount = \App\Models\ProductItem::where('product_id', $product->id)->where('status', 'available')->count();
$availableWeight = \App\Models\ProductItem::where('product_id', $product->id)->where('status', 'available')->sum('quantity');
echo " - Cantidad de bobinas disponibles: {$availableCount}\n";
echo " - Peso total de bobinas disponibles: {$availableWeight} kg\n";
