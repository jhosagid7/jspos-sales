<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = ['products', 'categories', 'suppliers', 'sales', 'orders', 'cargo_details', 'production_details', 'product_warehouse'];

echo "Table Row Counts:\n";
foreach ($tables as $table) {
    try {
        $count = DB::table($table)->count();
        echo "- $table: $count rows\n";
    } catch (\Exception $e) {
        echo "- $table: Error counting: " . $e->getMessage() . "\n";
    }
}
