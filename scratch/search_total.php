<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sales = \App\Models\Sale::where('total', 'like', '%254.45%')->orWhere('total_usd', 'like', '%254.45%')->get();
echo "Sales matching 254.45: " . count($sales) . "\n";
foreach ($sales as $s) {
    echo "Sale ID: {$s->id}, Number: {$s->invoice_number}, Total USD: {$s->total_usd}, Total Local: {$s->total}, Customer: {$s->customer->name}\n";
}

$orders = \App\Models\Order::where('total', 'like', '%254.45%')->get();
echo "Orders matching 254.45: " . count($orders) . "\n";
foreach ($orders as $o) {
    echo "Order ID: {$o->id}, Number: {$o->order_number}, Total Local: {$o->total}, Customer: {$o->customer->name}\n";
}
