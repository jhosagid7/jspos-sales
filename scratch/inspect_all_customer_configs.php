<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$configs = \App\Models\CustomerConfig::orderBy('id', 'desc')->take(10)->get();
echo "=== LATEST 10 CUSTOMER CONFIGS IN DB ===\n";
foreach ($configs as $cc) {
    echo "ID: {$cc->id}, Customer ID: {$cc->customer_id}, Customer Name: " . ($cc->customer?->name ?? 'None') . ", Comm: {$cc->commission_percent}%, Markup: {$cc->base_markup_percent}%, Freight: {$cc->freight_percent}%, Diff: {$cc->exchange_diff_percent}%, Created: {$cc->created_at}\n";
}
