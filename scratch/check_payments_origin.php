<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config(['database.connections.mysql.database' => 'pruebabderrorcobranza']);
\DB::purge('mysql');

$payments = \App\Models\Payment::where('collection_sheet_id', 71)->with('sale')->get();

echo "Total payments in Sheet 71: " . $payments->count() . "\n\n";

foreach ($payments->take(10) as $p) {
    echo "ID: {$p->id}, Pay Way: {$p->pay_way}, Amount: {$p->amount} {$p->currency}, Status: {$p->status}\n";
    if ($p->sale) {
        echo "  Sale ID: {$p->sale->id}, Sale Type: {$p->sale->type} (1 = cash/contado, 2 = credit?)\n";
        echo "  Sale Status: {$p->sale->status}, Total: {$p->sale->total}\n";
    } else {
        echo "  No sale linked (Debit Note?)\n";
    }
    echo "---------------------------\n";
}
