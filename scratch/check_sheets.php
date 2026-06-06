<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config(['database.connections.mysql.database' => 'pruebabderrorcobranza']);
\DB::purge('mysql');

$sheets = \App\Models\CollectionSheet::whereDate('opened_at', '2026-06-04')->get();
echo "Sheets on 2026-06-04:\n";
foreach ($sheets as $s) {
    echo "ID: {$s->id}, Number: {$s->sheet_number}, Opened At: {$s->opened_at}, Status: {$s->status}, Total Amount: {$s->total_amount}\n";
}
