<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;

echo "--- REPORTE DE RESERVAS DE INVENTARIO ---\n\n";

$reservations = OrderDetail::select('orders.status', DB::raw('count(DISTINCT orders.id) as order_count'), DB::raw('sum(quantity) as total_qty'))
    ->join('orders', 'order_details.order_id', '=', 'orders.id')
    ->whereNotIn('orders.status', ['paid', 'returned', 'cancelled', 'annulled'])
    ->groupBy('orders.status')
    ->get();

if ($reservations->isEmpty()) {
    echo "No hay reservas activas en este momento.\n";
} else {
    foreach ($reservations as $res) {
        echo "Estado: [" . str_pad($res->status, 12) . "] | Pedidos: " . str_pad($res->order_count, 3) . " | Cantidad Reservada: " . $res->total_qty . "\n";
    }
}

echo "\n--- ANÁLISIS DE PEDIDOS ANTIGUOS (> 15 días) ---\n";
$oldOrders = Order::whereNotIn('status', ['paid', 'returned', 'cancelled', 'annulled'])
    ->where('created_at', '<', now()->subDays(15))
    ->count();

echo "Pedidos 'clavaos' (viejos): " . $oldOrders . "\n";

if ($oldOrders > 0) {
    echo "Sugerencia: Sugiero anular o cancelar los pedidos viejos para liberar el stock en la App.\n";
}
