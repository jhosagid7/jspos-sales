<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;

$c = Customer::where('name', 'like', '%pedro%')->first();
if ($c) {
    echo "Customer: " . $c->name . "\n";
    echo "WA Notify Sales: " . ($c->whatsapp_notify_sales ? 'TRUE' : 'FALSE') . "\n";
    echo "EM Notify Sales: " . ($c->email_notify_sales ? 'TRUE' : 'FALSE') . "\n";
    echo "WA Mode: " . $c->wa_dispatch_mode . "\n";
    echo "EM Mode: " . $c->email_dispatch_mode . "\n";
} else {
    echo "Not found\n";
}
