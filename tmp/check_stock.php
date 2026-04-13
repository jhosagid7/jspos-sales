<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\OrderDetail;

$sku = 'BP03CT1EB';
$product = Product::where('sku', $sku)->first();

if (!$product) {
    echo "Producto no encontrado.\n";
    exit;
}

echo "REPORTE DE INVENTARIO PARA: " . $product->name . " (SKU: $sku)\n";
echo "------------------------------------------------------------\n";
echo "Fisico Global (tabla products): " . $product->stock_qty . "\n";

foreach ($product->warehouses as $w) {
    echo "Fisico en Almacen ID " . $w->id . " (" . $w->name . "): " . $w->pivot->stock_qty . "\n";
}

echo "\nBUSCANDO RESERVAS (Pedidos activos):\n";
$items = OrderDetail::where('product_id', $product->id)->get();
echo "Encontradas " . $items->count() . " lineas de pedido en total (sin filtrar).\n";

$reservadas = 0;
foreach ($items as $item) {
    $order = $item->order;
    if ($order) {
        echo "- Pedido #{$order->id} (Folio: {$order->order_number}) | Estado: {$order->status} | Almacen: {$item->warehouse_id} | Cantidad: {$item->quantity}\n";
        
        if (!in_array($order->status, ['paid', 'returned', 'cancelled', 'annulled'])) {
            $reservadas += $item->quantity;
        }
    } else {
        echo "- Detalle ID #{$item->id} SIN PEDIDO ASOCIADO (Error de integridad).\n";
    }
}

echo "\nTOTAL RESERVADAS (Calculadas): " . $reservadas . "\n";
echo "DISPONIBLE FINAL: " . ($product->stock_qty - $reservadas) . "\n";
