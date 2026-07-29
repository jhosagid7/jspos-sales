<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sessions = \DB::table('sessions')->get();
echo "Total sessions found: " . count($sessions) . "\n";

foreach ($sessions as $s) {
    // Decode base64 payload
    $data = unserialize(base64_decode($s->payload));
    if (isset($data['cart'])) {
        echo "\n=== SESSION ID: {$s->id} (Last Activity: " . date('Y-m-d H:i:s', $s->last_activity) . ") ===\n";
        $cart = $data['cart'];
        if (is_object($cart)) {
            $cart = $cart->toArray();
        }
        echo "Customer Config: " . (isset($data['customerConfig']) ? json_encode($data['customerConfig']) : "None") . "\n";
        echo "Apply Commissions: " . (isset($data['applyCommissions']) ? ($data['applyCommissions'] ? "true" : "false") : "None") . "\n";
        echo "Apply Freight: " . (isset($data['applyFreight']) ? ($data['applyFreight'] ? "true" : "false") : "None") . "\n";
        echo "Freight Broken Down: " . (isset($data['is_freight_broken_down']) ? ($data['is_freight_broken_down'] ? "true" : "false") : "None") . "\n";
        echo "Cart Items:\n";
        foreach ($cart as $item) {
            echo "  PID: {$item['pid']}, Name: " . (\App\Models\Product::find($item['pid'])?->name ?? 'Unknown') . ", Qty: {$item['qty']}, Base Price: " . ($item['base_price'] ?? 'None') . ", Sale Price: " . ($item['sale_price'] ?? 'None') . ", Total: " . ($item['total'] ?? 'None') . "\n";
        }
    }
}
