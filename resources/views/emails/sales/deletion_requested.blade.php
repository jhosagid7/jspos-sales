<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .details { margin-top: 20px; border-collapse: collapse; width: 100%; }
        .details th, .details td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .details th { background-color: #f2f2f2; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #333; color: #fff; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h2>Solicitud de Eliminación de Venta</h2>
    <p>El usuario <strong>{{ $requester->name }}</strong> ha solicitado eliminar la venta #{{ $sale->id }}.</p>
    
    <p><strong>Motivo:</strong> {{ $sale->deletion_reason }}</p>
    
    <table class="details">
        <tr>
            <th>Folio</th>
            <td>{{ $sale->id }}</td>
        </tr>
        <tr>
            <th>Cliente</th>
            <td>{{ $sale->customer->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Total</th>
            <td>${{ number_format($sale->total, 2) }}</td>
        </tr>
        <tr>
            <th>Fecha Venta</th>
            <td>{{ $sale->created_at }}</td>
        </tr>
    </table>

    <p style="margin-top: 20px;">
        Por favor ingrese al sistema para aprobar o rechazar esta solicitud.
    </p>
</body>
</html>
