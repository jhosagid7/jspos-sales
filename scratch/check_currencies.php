<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$currencies = \App\Models\Currency::all();
echo "=== Currencies ===\n";
foreach ($currencies as $c) {
    echo "ID: {$c->id}, Code: {$c->code}, Symbol: {$c->symbol}, Exchange Rate: {$c->exchange_rate}, Is Primary: {$c->is_primary}\n";
}
