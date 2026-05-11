<?php
use App\Models\Product;
use App\Models\Transfer;

$p = Product::find(466);
if ($p) {
    echo "Product: {$p->name} (ID: {$p->id}) Global Stock: {$p->stock_qty}\n";
    foreach($p->productWarehouses as $pw) {
        $wname = \App\Models\Warehouse::find($pw->warehouse_id)->name ?? 'Unknown';
        echo "  - Warehouse {$pw->warehouse_id} ({$wname}): {$pw->stock_qty}\n";
    }
}

echo "\n--- TRASPASOS RECIENTES ---\n";
$lastTransfers = Transfer::orderBy('id', 'desc')->take(10)->get();
foreach($lastTransfers as $t) {
    $from = \App\Models\Warehouse::find($t->from_warehouse_id)->name ?? '??';
    $to = \App\Models\Warehouse::find($t->to_warehouse_id)->name ?? '??';
    echo "ID: {$t->id}, From: {$from} To: {$to}, Status: {$t->status}, Date: {$t->created_at}\n";
    foreach($t->details as $d) {
        if($d->product_id == 466) {
            echo "  >>> PRODUCT 466: Qty: {$d->quantity}\n";
        }
    }
}

