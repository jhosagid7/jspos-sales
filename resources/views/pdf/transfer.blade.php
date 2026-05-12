<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Traspaso #{{ $transfer->id }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; text-transform: uppercase; }
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 5px; vertical-align: top; }
        .label { font-weight: bold; width: 120px; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .details-table th { background-color: #f2f2f2; border: 1px solid #ddd; padding: 8px; text-align: left; }
        .details-table td { border: 1px solid #ddd; padding: 8px; }
        .footer { margin-top: 50px; text-align: center; font-style: italic; font-size: 10px; }
        .signature-box { margin-top: 60px; width: 100%; }
        .signature { border-top: 1px solid #000; width: 200px; display: inline-block; margin: 0 50px; padding-top: 5px; }
        .status-badge { display: inline-block; padding: 5px 10px; border-radius: 4px; font-weight: bold; text-transform: uppercase; }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-partial { background-color: #d1ecf1; color: #0c5460; }
        .status-dispatched { background-color: #cce5ff; color: #004085; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Comprobante de Traspaso de Inventario</div>
        <div>Control de Logística JSPOS</div>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Traspaso ID:</td>
            <td>#{{ $transfer->id }}</td>
            <td class="label">Fecha:</td>
            <td>{{ $transfer->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Origen:</td>
            <td>{{ $transfer->fromWarehouse->name }}</td>
            <td class="label">Destino:</td>
            <td>{{ $transfer->toWarehouse->name }}</td>
        </tr>
        <tr>
            <td class="label">Creado por:</td>
            <td>{{ $transfer->user->name ?? 'N/A' }}</td>
            <td class="label">Despachado por:</td>
            <td>{{ $transfer->dispatchedBy->name ?? 'Pendiente' }}</td>
        </tr>
        <tr>
            <td class="label">Recibido por:</td>
            <td>{{ $transfer->receivedBy->name ?? 'Pendiente' }}</td>
            <td class="label">Estado:</td>
            <td>
                <span class="status-badge status-{{ strtolower(str_replace(' ', '_', $transfer->status)) }}">
                    {{ $transfer->status }}
                </span>
            </td>
        </tr>
        @if($transfer->note)
        <tr>
            <td class="label">Nota:</td>
            <td colspan="3">{{ $transfer->note }}</td>
        </tr>
        @endif
        @if($transfer->rejection_reason)
        <tr>
            <td class="label">Motivo Rechazo:</td>
            <td colspan="3" style="color: red;">{{ $transfer->rejection_reason }}</td>
        </tr>
        @endif
    </table>

    <div style="font-weight: bold; margin-bottom: 5px;">Detalle de Productos:</div>
    <table class="details-table">
        <thead>
            <tr>
                <th>Producto / SKU</th>
                <th style="text-align: center; width: 80px;">Enviado</th>
                <th style="text-align: center; width: 80px;">Recibido</th>
                <th style="text-align: center; width: 80px;">Rechazado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transfer->details as $detail)
            <tr>
                <td>{{ $detail->product->name }} ({{ $detail->product->sku }})</td>
                <td style="text-align: center;">{{ number_format($detail->quantity, 2) }}</td>
                <td style="text-align: center;">{{ number_format($detail->received_quantity, 2) }}</td>
                <td style="text-align: center;">{{ number_format($detail->rejected_quantity, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f9f9f9; font-weight: bold;">
                <td style="text-align: right;">TOTALES:</td>
                <td style="text-align: center;">{{ number_format($transfer->details->sum('quantity'), 2) }}</td>
                <td style="text-align: center;">{{ number_format($transfer->details->sum('received_quantity'), 2) }}</td>
                <td style="text-align: center;">{{ number_format($transfer->details->sum('rejected_quantity'), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="signature-box" style="text-align: center;">
        <div style="display: inline-block;">
            <div class="signature">
                Despacho (Origen)<br>
                <small>{{ $transfer->dispatchedBy->name ?? '________________' }}</small>
            </div>
        </div>
        <div style="display: inline-block;">
            <div class="signature">
                Recepción (Destino)<br>
                <small>{{ $transfer->receivedBy->name ?? '________________' }}</small>
            </div>
        </div>
    </div>

    <div class="footer">
        Documento generado automáticamente por el sistema JSPOS el {{ date('d/m/Y H:i') }}.
    </div>
</body>
</html>
