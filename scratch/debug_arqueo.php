<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config(['database.connections.mysql.database' => 'pruebabderrorcobranza']);
\DB::purge('mysql');

echo "--- CONFIGURED BANKS in banks table ---\n";
$banks = \DB::table('banks')->get();
foreach ($banks as $b) {
    echo " - ID: {$b->id} | Name: '{$b->name}' | Account: '{$b->account_number}' | Status: " . ($b->status ?? 'N/A') . "\n";
}
