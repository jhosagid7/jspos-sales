<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config(['database.connections.mysql.database' => 'pruebabderrorcobranza']);
\DB::purge('mysql');

$columns = \Schema::getColumnListing('payments');
echo "Payments Columns: " . implode(', ', $columns) . "\n\n";

$targetIds = [1275, 1295, 1296, 1297, 1305, 1310];
$payments = \App\Models\Payment::whereIn('id', $targetIds)->get();

foreach ($payments as $p) {
    echo "ID: {$p->id}\n";
    echo "Status: {$p->status}\n";
    echo "Created At: {$p->created_at}\n";
    echo "Updated At: {$p->updated_at}\n";
    echo "Approved At (if exists): " . ($p->approved_at ?? 'N/A') . "\n";
    echo "---------------------------\n";
}
