<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$query = App\Models\Order::where('status', 'pending');

$totalSalePriceQty = App\Models\OrderDetail::whereIn('order_id', $query->clone()->pluck('id'))
    ->sum(DB::raw('sale_price * quantity'));

$calculatedBaseTotal = 0;
foreach ($query->get() as $order) {
    $increments = ($order->resolved_commission_percent + $order->resolved_freight_percent + $order->resolved_exchange_diff_percent) / 100;
    $baseAmount = $order->total / (1 + $increments);
    $calculatedBaseTotal += $baseAmount;
}

echo "Sum of sale_price * quantity: " . $totalSalePriceQty . PHP_EOL;
echo "Sum of calculated base amounts: " . $calculatedBaseTotal . PHP_EOL;
echo "Number of pending orders: " . $query->count() . PHP_EOL;
