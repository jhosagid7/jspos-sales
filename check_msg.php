<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = App\Models\Customer::find(9);
echo "Name: " . $c->name . "\n";
echo "Email Notify Sales (Prop): " . var_export($c->email_notify_sales, true) . "\n";
echo "RAW DB value: " . var_export(DB::table('customers')->where('id', 9)->value('email_notify_sales'), true) . "\n";
