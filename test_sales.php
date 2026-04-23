<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\Customer::where('name', 'like', '%DALBERT RAMIREZ%')->first();
if ($user) {
    echo "ID: " . $user->id . " | Name: " . $user->name . " | Email: " . $user->email . "\n";
    $sales = \App\Models\Sale::where('customer_id', $user->id)->where('status', 'pending')->get();
    echo "Total pending sales for user: " . $sales->count() . "\n";
    foreach($sales as $s) {
        echo "Sale ID: {$s->id} | Invoice: {$s->invoice_number} | Type: {$s->type} | Customer ID: {$s->customer_id}\n";
    }
} else {
    echo "User not found\n";
}
