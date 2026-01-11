<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$product = App\Models\Product::where('name', 'like', '%laptop%')->first();
if ($product) {
    echo "Product found: {$product->name} (ID: {$product->id})\n";
    $product->load('warehouses');
    foreach ($product->warehouses as $w) {
        echo "Warehouse: {$w->name} (ID: {$w->id}) - Stock: {$w->pivot->stock_qty}\n";
    }
    
    $reserved = $product->getReservedStock(1); // Assuming warehouse 1
    echo "Reserved in Warehouse 1: {$reserved}\n";
} else {
    echo "Product not found\n";
}

$orders = App\Models\Order::where('status', 'pending')->with('details')->get();
echo "Pending Orders Count: " . $orders->count() . "\n";
foreach ($orders as $order) {
    echo "Order ID: {$order->id}\n";
    foreach ($order->details as $detail) {
        echo "  - Product ID: {$detail->product_id}, Qty: {$detail->quantity}, Warehouse: {$detail->warehouse_id}\n";
    }
}
