<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orders = \App\Models\Order::orderBy('created_at', 'desc')->take(20)->get();
echo "=== Recent Orders ===\n";
foreach ($orders as $o) {
    $count = $o->details->count();
    $total_base = $o->details->sum(function($d) {
        return floatval($d->regular_price ?? $d->price) * floatval($d->quantity);
    });
    echo "OrderID: {$o->id}, No: {$o->order_number}, Customer ID: {$o->customer_id}, Name: " . ($o->customer?->name ?? 'Unknown') . ", Items: $count, Total: {$o->total}, Base Sum: $total_base, Created: {$o->created_at}\n";
    if ($count == 5 || abs($total_base - 175.2615) < 1.0) {
        echo "  *** Match! Details:\n";
        foreach ($o->details as $d) {
            echo "    Prod ID: {$d->product_id}, Qty: {$d->quantity}, Price: {$d->price}, Reg Price: {$d->regular_price}\n";
        }
    }
}
