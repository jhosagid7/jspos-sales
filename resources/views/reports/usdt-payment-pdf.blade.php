<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Pago USDT - {{ $record->reference }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; margin: 0; padding: 15px; }
        .header { width: 100%; text-align: center; margin-bottom: 15px; border-bottom: 2px solid #3B3F5C; padding-bottom: 10px; }
        .company-name { font-size: 18px; font-weight: bold; color: #3B3F5C; }
        .title { font-size: 13px; font-weight: bold; margin-top: 10px; margin-bottom: 10px; background-color: #f2f2f2; padding: 5px 10px; border-left: 3px solid #3B3F5C; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px 10px; text-align: left; }
        th { background-color: #f9f9f9; font-weight: bold; width: 35%; }
        .image-container { text-align: center; margin-top: 15px; }
        .voucher-image { max-width: 95%; max-height: 500px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; }
        .no-image { text-align: center; padding: 40px; border: 1px dashed #ccc; background-color: #fafafa; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('app.name', 'JSPOS') }}</div>
        <div style="font-size: 12px; margin-top: 5px; color: #555;">Comprobante Individual de Pago USDT ($)</div>
    </div>

    <div class="title">Datos del Pago USDT</div>
    <table>
        <tr>
            <th>Fecha del Comprobante</th>
            <td>{{ \Carbon\Carbon::parse($record->usdt_date)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <th>N° TxID / Referencia</th>
            <td><strong>{{ $record->reference }}</strong></td>
        </tr>
        <tr>
            <th>Billetera / Emisor</th>
            <td>{{ $record->sender_name }}</td>
        </tr>
        <tr>
            <th>Monto USDT</th>
            <td>${{ number_format($record->amount, 2) }}</td>
        </tr>
        <tr>
            <th>Saldo Restante</th>
            <td>${{ number_format($record->remaining_balance, 2) }}</td>
        </tr>
    </table>

    <div class="image-container">
        <div class="title" style="text-align: left;">Capture / Comprobante Adjunto</div>
        @if($record->image_path && file_exists(storage_path('app/public/' . $record->image_path)))
            <img src="{{ storage_path('app/public/' . $record->image_path) }}" class="voucher-image" alt="Comprobante USDT">
        @else
            <div class="no-image">No hay imagen adjunta para este comprobante USDT.</div>
        @endif
    </div>
</body>
</html>
