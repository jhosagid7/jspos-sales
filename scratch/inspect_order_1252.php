<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$o = \App\Models\Order::find(1252);
if ($o) {
    echo "=== ORDER 1252 DETAILS ===\n";
    echo "Order ID: {$o->id}, Number: {$o->order_number}, Total Local: {$o->total}, Created: {$o->created_at}\n";
    echo "Items:\n";
    foreach ($o->details as $d) {
        echo "  Prod ID: {$d->product_id}, Name: " . ($d->product?->name) . ", Qty: {$d->quantity}, Price: {$d->price}\n";
    }
} else {
    echo "Order 1252 not found.\n";
}
