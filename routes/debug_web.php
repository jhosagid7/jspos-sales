<?php

use Illuminate\Support\Facades\Route;
use App\Models\Sale;
use App\Models\SalePaymentDetail;

Route::get('/debug-payments', function () {
    $lastSale = Sale::latest()->first();
    
    if (!$lastSale) {
        return "No hay ventas registradas.";
    }

    $details = SalePaymentDetail::where('sale_id', $lastSale->id)->get();

    echo "<h1>Diagnóstico de la Última Venta (ID: {$lastSale->id})</h1>";
    echo "<p><strong>Tipo de Venta:</strong> {$lastSale->type}</p>";
    echo "<p><strong>Total:</strong> {$lastSale->total}</p>";
    echo "<p><strong>Creada:</strong> {$lastSale->created_at}</p>";
    
    echo "<h2>Detalles de Pago Guardados:</h2>";
    
    if ($details->isEmpty()) {
        echo "<p style='color: red;'>NO hay detalles de pago guardados para esta venta.</p>";
        echo "<p>Esto explica por qué no ves el desglose. El problema está en el guardado (Sales.php).</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Moneda</th><th>Banco</th><th>Monto</th></tr>";
        foreach ($details as $detail) {
            echo "<tr>";
            echo "<td>{$detail->id}</td>";
            echo "<td>{$detail->currency_code}</td>";
            echo "<td>" . ($detail->bank_name ?? 'N/A') . "</td>";
            echo "<td>{$detail->amount}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='color: green;'>SI hay detalles guardados. Si no los ves en el reporte, el problema está en la vista (CashCount.php).</p>";
    }
    
    // Check table structure
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('sale_payment_details');
    echo "<h2>Columnas en la tabla sale_payment_details:</h2>";
    echo implode(', ', $columns);
});
