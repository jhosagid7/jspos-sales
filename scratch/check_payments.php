<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config(['database.connections.mysql.database' => 'pruebabderrorcobranza']);
\DB::purge('mysql');

$sheet = \App\Models\CollectionSheet::find(71);
if (!$sheet) {
    die("Sheet 71 not found");
}

echo "Sheet Info:\n";
echo "ID: " . $sheet->id . "\n";
echo "Number: " . $sheet->sheet_number . "\n";
echo "Opened At: " . $sheet->opened_at . "\n";
echo "Closed At: " . $sheet->closed_at . "\n";
echo "Status: " . $sheet->status . "\n";
echo "\nPayments in Sheet:\n";

$payments = $sheet->payments()->with(['sale.customer', 'user', 'zelleRecord', 'bankRecord'])->get();

foreach ($payments as $p) {
    echo "--------------------------------------------------\n";
    echo "Payment ID: " . $p->id . "\n";
    echo "Pay Way: " . $p->pay_way . "\n";
    echo "Amount: " . $p->amount . " " . $p->currency . "\n";
    echo "Status: " . $p->status . "\n";
    echo "Reference/Deposit No: " . ($p->deposit_number ?? ($p->zelleRecord->reference ?? $p->bankRecord->reference ?? 'N/A')) . "\n";
    echo "Payment Date: " . $p->payment_date . "\n";
    echo "Created At: " . $p->created_at . "\n";
    echo "Approved At: " . ($p->approved_at ?? 'N/A') . "\n";
    echo "User ID: " . $p->user_id . " (" . ($p->user->name ?? 'N/A') . ")\n";
    echo "Customer: " . ($p->sale->customer->name ?? 'N/A') . "\n";
}
