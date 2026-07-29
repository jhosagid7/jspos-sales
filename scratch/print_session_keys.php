<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sessions = \DB::table('sessions')->get();
foreach ($sessions as $s) {
    $data = unserialize(base64_decode($s->payload));
    if (isset($data['pos'])) {
        echo "=== POS Session Data ===\n";
        print_r($data['pos']);
    }
    // Let's print everything else that could be relevant
    foreach ($data as $k => $v) {
        if (is_array($v) && ($k === 'cart' || str_contains($k, 'cart') || str_contains($k, 'sale'))) {
            echo "Key: $k\n";
            print_r($v);
        }
    }
}
