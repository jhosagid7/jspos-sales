<?php

use App\Models\User;
use App\Models\SellerConfig;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$seller = User::find(3); // Elizabeth

if ($seller) {
    echo "Seller: " . $seller->name . "\n";
    $config = $seller->latestSellerConfig;
    
    if ($config) {
        echo "Has Config: YES\n";
        echo "Commission %: " . $config->commission_percent . "\n";
    } else {
        echo "Has Config: NO\n";
    }
} else {
    echo "Seller ID 3 not found.\n";
}
