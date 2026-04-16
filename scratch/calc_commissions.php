<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = 8;
$sales = App\Models\Sale::whereHas('customer', function($q) use ($id) {
    $q->where('seller_id', $id);
})->get();

$totalGanado = $sales->where('status', 'paid')->sum('final_commission_amount');
$pagadoAlVendedor = $sales->where('commission_status', 'paid')->sum('final_commission_amount');
$pendienteAlVendedor = $sales->where('status', 'paid')->where('commission_status', 'pending_payment')->sum('final_commission_amount');

echo "Total Ganado (Cliente Pago): " . $totalGanado . "\n";
echo "Pagado al Vendedor: " . $pagadoAlVendedor . "\n";
echo "Pendiente por Pagar al Vendedor: " . $pendienteAlVendedor . "\n";
