<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $returns = DB::select("
        SELECT 
            sr.id as return_id,
            s.id as sale_id, 
            s.invoice_number, 
            s.created_at as sale_date, 
            s.total_usd as sale_total,
            sr.created_at as return_date, 
            sr.total_returned as amount_returned, 
            sr.refund_method as method 
        FROM sale_returns sr 
        LEFT JOIN sales s ON sr.sale_id = s.id 
        ORDER BY sr.created_at DESC 
        LIMIT 20
    ");

    echo "--- ÚLTIMAS 20 NOTAS DE CRÉDITO ---\n";
    foreach ($returns as $r) {
        printf(
            "ID: %d | Factura: %-10s | Venta: %s | Dev: %s | Monto: %-10.4f | Método: %-15s\n",
            $r->return_id,
            $r->invoice_number ?? 'N/A',
            substr($r->sale_date, 0, 10),
            substr($r->return_date, 0, 10),
            $r->amount_returned,
            $r->method
        );
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
