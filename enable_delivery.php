<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$config = \App\Models\Configuration::first();
if (!$config) {
    echo "NO CONFIG FOUND\n";
    exit;
}

$modules = explode(',', $config->modules);
if (!in_array('module_delivery', $modules)) {
    $modules[] = 'module_delivery';
    $config->modules = implode(',', $modules);
    $config->save();
    echo "MODULE_DELIVERY_ENABLED\n";
} else {
    echo "ALREADY_ENABLED\n";
}
