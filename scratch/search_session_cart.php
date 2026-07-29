<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sessions = \DB::table('sessions')->get();
echo "Total sessions to search: " . count($sessions) . "\n";
foreach ($sessions as $s) {
    $data = unserialize(base64_decode($s->payload));
    
    // Check if this session is for customer ID 28
    $isTargetCustomer = false;
    if (isset($data['sale_customer']) && isset($data['sale_customer']['id']) && $data['sale_customer']['id'] == 28) {
        $isTargetCustomer = true;
    } elseif (isset($data['customer']) && isset($data['customer']['id']) && $data['customer']['id'] == 28) {
        $isTargetCustomer = true;
    }
    
    if ($isTargetCustomer) {
        echo "=== FOUND SESSION ID: {$s->id} ===\n";
        echo "Last Activity: " . date('Y-m-d H:i:s', $s->last_activity) . "\n";
        echo "Customer Config loaded: " . (isset($data['customerConfig']) ? json_encode($data['customerConfig']) : "None") . "\n";
        if (isset($data['cart'])) {
            $cart = $data['cart'];
            if (is_object($cart)) $cart = $cart->toArray();
            echo "Cart count: " . count($cart) . "\n";
            print_r($cart);
        } else {
            echo "No cart in this session.\n";
        }
    }
}
