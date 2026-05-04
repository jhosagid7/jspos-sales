<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$product = Product::withTrashed()->find(133);

if ($product) {
    echo "Product found:\n";
    echo "ID: " . $product->id . "\n";
    echo "Name: " . $product->name . "\n";
    echo "Status: " . $product->status . "\n";
    echo "Deleted At: " . ($product->deleted_at ?? 'NULL') . "\n";
    echo "Category ID: " . ($product->category_id ?? 'NULL') . "\n";
    echo "Warehouse ID (main): " . ($product->warehouse_id ?? 'NULL') . "\n";
} else {
    echo "Product NOT found with ID 133\n";
}
