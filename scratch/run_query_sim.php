<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$sheet = \App\Models\CollectionSheet::find(71);

$query = $sheet->payments()->with(['sale.customer', 'user', 'zelleRecord'])->whereIn('status', ['approved', 'voided']);

$payments = $query->get();

echo "Total payments in sheet 71: " . $payments->count() . "\n";
foreach ($payments as $p) {
    echo "ID: {$p->id}, PayWay: {$p->pay_way}, Amount: {$p->amount}, Currency: {$p->currency}, Ref: {$p->deposit_number}, Status: {$p->status}, User: {$p->user->name}, Date: {$p->payment_date}\n";
}
