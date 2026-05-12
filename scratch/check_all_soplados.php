<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$products = \App\Models\Product::whereHas('tags', function($q){
    $q->where('name', 'soplados');
})->get(['id', 'name']);

echo "Products with 'soplados' tag:\n";
foreach($products as $p) {
    $hasFormula = \App\Models\ProductionFormula::where('product_id', $p->id)->exists();
    echo "ID: " . $p->id . " | Name: " . $p->name . " | Has Formula: " . ($hasFormula ? 'YES' : 'NO') . "\n";
}
