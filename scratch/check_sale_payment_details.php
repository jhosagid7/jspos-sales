<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config(['database.connections.mysql.database' => 'pruebabderrorcobranza']);
\DB::purge('mysql');

$columns = \Schema::getColumnListing('sale_payment_details');
echo "SalePaymentDetail Columns: " . implode(', ', $columns) . "\n";
