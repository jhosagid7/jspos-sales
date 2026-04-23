<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$customers = \App\Models\Customer::where('email', 'dalbert3433@gmail.com')->get();
foreach($customers as $c) {
    echo "ID: {$c->id} | Name: {$c->name} | Email: {$c->email}\n";
    $sales = \App\Models\Sale::where('customer_id', $c->id)->where('status','pending')->count();
    echo "Pending sales: $sales\n";
}
