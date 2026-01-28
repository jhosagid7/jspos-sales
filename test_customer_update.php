<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Update customer
$customer = App\Models\Customer::find(4);
$customer->allow_credit = true;
$customer->credit_days = 30;
$customer->credit_limit = 5000;
$customer->usd_payment_discount = 5;
$customer->save();

echo "Customer updated:\n";
echo json_encode($customer->only(['id', 'name', 'allow_credit', 'credit_days', 'credit_limit', 'usd_payment_discount']), JSON_PRETTY_PRINT);
echo "\n";
