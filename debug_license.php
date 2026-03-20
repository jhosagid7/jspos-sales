<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$license = \App\Models\License::latest('created_at')->first();

if (!$license) {
    echo "NO LICENSE FOUND\n";
    exit;
}

$decoded = base64_decode($license->license_key);
$parts = explode('||', $decoded);

if (count($parts) > 0) {
    echo "LICENSE PAYLOAD:\n";
    echo $parts[0] . "\n";
    
    $data = json_decode($parts[0], true);
    if ($data && isset($data['modules'])) {
        echo "\nACTIVE MODULES:\n";
        print_r($data['modules']);
    } else {
        echo "\nNO MODULES DEFINED IN PAYLOAD\n";
    }
}
