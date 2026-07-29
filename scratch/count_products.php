<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = \App\Models\Product::count();
echo "Total products in database: $count\n";

// Let's print the first 10 products and their prices
$products = \App\Models\Product::take(20)->get();
foreach ($products as $p) {
    echo "ID: {$p->id}, SKU: {$p->sku}, Name: {$p->name}, Price: {$p->price}\n";
}
