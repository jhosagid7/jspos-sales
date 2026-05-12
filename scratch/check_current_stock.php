<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductWarehouse;
use App\Models\Product;

$productId = 466;
$warehouseId = 3;

$pw = ProductWarehouse::where('product_id', $productId)->where('warehouse_id', $warehouseId)->first();
$product = Product::find($productId);

echo "Product: " . ($product->name ?? 'N/A') . " (ID: $productId)\n";
echo "Warehouse ID: $warehouseId\n";
echo "Current Stock in Database: " . ($pw->stock_qty ?? '0') . "\n";
echo "Global Stock in Product table: " . ($product->stock_qty ?? '0') . "\n";
