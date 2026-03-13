<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Permission;

$p = Permission::where('name', 'payments.create_credit_note')->first();
if ($p) {
    echo "Permission exists\n";
} else {
    echo "Permission does not exist\n";
    Permission::create(['name' => 'payments.create_credit_note']);
    echo "Permission created\n";
}
