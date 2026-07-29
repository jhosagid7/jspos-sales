<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$configs = \App\Models\CustomerConfig::where('commission_percent', 4.00)
    ->where('base_markup_percent', 4.00)
    ->get();
echo "Total configurations with 4% comm and 4% markup: " . count($configs) . "\n";
foreach ($configs as $cc) {
    echo "ID: {$cc->id}, Customer ID: {$cc->customer_id}, Customer Name: " . ($cc->customer?->name ?? 'None') . ", Diff: {$cc->exchange_diff_percent}%\n";
}
