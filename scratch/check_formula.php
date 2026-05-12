<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductionFormula;

$sku = 'BM05B18AZ';
$product = Product::where('sku', $sku)->first();

if (!$product) {
    echo "Product $sku not found\n";
    exit;
}

echo "Product: " . $product->name . " (ID: " . $product->id . ")\n";

$formulas = ProductionFormula::where('product_id', $product->id)->with('ingredient')->get();

if ($formulas->isEmpty()) {
    echo "No formula found for this product.\n";
} else {
    echo "Formula ingredients:\n";
    foreach ($formulas as $f) {
        echo "- " . ($f->ingredient->name ?? 'Unknown') . " (ID: " . $f->ingredient_id . "): " . $f->quantity . " units per unit of finished product.\n";
    }
}
