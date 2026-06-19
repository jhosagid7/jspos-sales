<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

$columns = Schema::getColumnListing('cargo_details');
echo "Columns of cargo_details:\n";
foreach ($columns as $column) {
    echo "- " . $column . " (type: " . Schema::getColumnType('cargo_details', $column) . ")\n";
}
