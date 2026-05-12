<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$productIdsWithFormula = \App\Models\ProductionFormula::distinct()->pluck('product_id');
$products = \App\Models\Product::whereIn('id', $productIdsWithFormula)->with('tags')->get();
echo "Products with formulas:\n";
echo "\nSimulating API Query (whereHas tags soplados):\n";
$productIds = \App\Models\ProductionFormula::distinct()->pluck('product_id');
$apiProducts = \App\Models\Product::whereIn('id', $productIds)
    ->whereHas('tags', function($q) {
        $q->where('name', 'soplados');
    })
    ->get(['id', 'name', 'sku']);

foreach($apiProducts as $p) {
    echo "ID: " . $p->id . " | Name: " . $p->name . "\n";
}
if($apiProducts->isEmpty()) echo "No products found with tag 'soplados' and a formula.\n";
