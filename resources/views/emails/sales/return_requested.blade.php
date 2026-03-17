<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { width: 80%; margin: 20px auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
        .header { background: #f8f9fa; padding: 10px; border-bottom: 2px solid #007bff; margin-bottom: 20px; }
        .details { margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background: #f2f2f2; }
        .footer { font-size: 0.9em; color: #777; margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; }
        .badge { background: #ffc107; color: #000; padding: 3px 8px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Solicitud de Devolución <span class="badge">PENDIENTE</span></h2>
        </div>
        
        <div class="details">
            <p><strong>Solicitante:</strong> {{ $requester->name }}</p>
            <p><strong>Factura:</strong> {{ $saleReturn->sale->invoice_number ?? $saleReturn->sale_id }}</p>
            <p><strong>Cliente:</strong> {{ $saleReturn->customer->name }}</p>
            <p><strong>Monto Total a Devolver:</strong> ${{ number_format($saleReturn->total_returned, 2) }}</p>
            <p><strong>Metodo de Reembolso:</strong> {{ strtoupper(str_replace('_', ' ', $saleReturn->refund_method)) }}</p>
            <p><strong>Motivo:</strong> {{ $saleReturn->reason }}</p>
        </div>

        <h3>Detalles de Productos:</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cant.</th>
                    <th>P. Unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($saleReturn->details as $item)
                <tr>
                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                    <td>{{ $item->quantity_returned }}</td>
                    <td>${{ number_format($item->unit_price, 2) }}</td>
                    <td>${{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <p style="margin-top: 20px;">
            Por favor, ingrese al sistema para aprobar o rechazar esta solicitud.
        </p>

        <div class="footer">
            Este es un correo automático generado por el sistema {{ config('app.name') }}.
        </div>
    </div>
</body>
</html>
