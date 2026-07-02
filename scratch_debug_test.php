<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$category = \App\Models\Category::create(['name' => 'Zanahorias']);
$supplier = \App\Models\Supplier::create(['name' => 'El Proveedor']);
$warehouse = \App\Models\Warehouse::create([
    'id' => 1,
    'name' => 'TIENDA PRINCIPAL',
    'is_active' => 1,
]);

\App\Models\Configuration::create([
    'business_name' => 'Test Business',
    'default_warehouse_id' => $warehouse->id,
]);

$product = \App\Models\Product::create([
    'name' => 'BOBINA DE ZANAHORIA 1KG',
    'sku' => 'B04BOZHB',
    'price' => 10,
    'cost' => 5,
    'manage_stock' => 1,
    'stock_qty' => 0,
    'low_stock' => 1,
    'category_id' => $category->id,
    'supplier_id' => $supplier->id,
    'is_variable_quantity' => true,
]);

\App\Models\ProductWarehouse::create([
    'product_id' => $product->id,
    'warehouse_id' => $warehouse->id,
    'stock_qty' => 0,
]);

\App\Models\ProductItem::create([
    'product_id' => $product->id,
    'warehouse_id' => $warehouse->id,
    'quantity' => 25.50,
    'original_quantity' => 25.50,
    'status' => 'available',
]);

\App\Models\ProductItem::create([
    'product_id' => $product->id,
    'warehouse_id' => $warehouse->id,
    'quantity' => 20.00,
    'original_quantity' => 20.00,
    'status' => 'available',
]);

$form = new \App\Livewire\Forms\PostProduct();
// Load form values as in Products::Edit
$form->product_id = $product->id;
$form->name = $product->name;
$form->sku = $product->sku;
$form->cost = $product->cost;
$form->price = $product->price;
$form->manage_stock = $product->manage_stock;

if ($product->is_variable_quantity) {
    $form->stock_qty = \App\Models\ProductItem::where('product_id', $product->id)
        ->where('status', 'available')
        ->sum('quantity');
} else {
    $form->stock_qty = $product->stock_qty;
}

$form->low_stock = $product->low_stock;
$form->category_id = $product->category_id;
$form->supplier_id = $product->supplier_id;
$form->is_variable_quantity = (bool) $product->is_variable_quantity;

echo "Before update: form->stock_qty = {$form->stock_qty}\n";

$form->update();

$product->refresh();
echo "After update: product->stock_qty = {$product->stock_qty}\n";

$pw = \App\Models\ProductWarehouse::where('product_id', $product->id)
    ->where('warehouse_id', $warehouse->id)
    ->first();
echo "After update: pw->stock_qty = {$pw->stock_qty}\n";
