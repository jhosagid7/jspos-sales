<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Capturas USDT Filtradas</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; }
        .page { page-break-after: always; padding: 10px; }
        .page:last-child { page-break-after: avoid; }
        .header { width: 100%; text-align: center; margin-bottom: 15px; border-bottom: 2px solid #3B3F5C; padding-bottom: 10px; }
        .company-name { font-size: 16px; font-weight: bold; color: #3B3F5C; }
        .title { font-size: 13px; font-weight: bold; margin-top: 10px; margin-bottom: 10px; background-color: #f2f2f2; padding: 5px 10px; border-left: 3px solid #3B3F5C; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ddd; padding: 5px 8px; text-align: left; }
        th { background-color: #f9f9f9; font-weight: bold; width: 40%; }
        .text-right { text-align: right; }
        .usdt-image-container { text-align: center; margin-top: 5px; }
        .usdt-image { max-width: 95%; max-height: 480px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; box-shadow: 1px 1px 5px rgba(0,0,0,0.1); }
        .no-image { text-align: center; padding: 40px; border: 1px dashed #ccc; border-radius: 4px; background-color: #fafafa; font-size: 12px; color: #777; margin-top: 5px; }
        .grid-container { width: 100%; }
        .col-left { width: 48%; float: left; }
        .col-right { width: 48%; float: right; }
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>
    @forelse($records as $index => $record)
        <div class="page">
            <div class="header">
                <div class="company-name">{{ config('app.name', 'JSPOS') }}</div>
                <div style="font-size: 11px; margin-top: 3px; color: #555;">
                    Reporte de Capturas USDT ($) ({{ $index + 1 }} de {{ count($records) }})
                </div>
            </div>

            <div class="grid-container clearfix">
                <div class="col-left">
                    <div class="title">Datos del Pago USDT</div>
                    <table>
                        <tr>
                            <th>Fecha</th>
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
                            <th>Monto Original</th>
                            <td>${{ number_format($record->amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Saldo Restante</th>
                            <td>${{ number_format($record->remaining_balance, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Estado</th>
                            <td>
                                <span style="font-weight: bold; color: {{ $record->status == 'unused' ? '#28a745' : ($record->status == 'partial' ? '#fd7e14' : '#6c757d') }}">
                                    {{ $record->status == 'unused' ? 'Sin Usar' : ($record->status == 'partial' ? 'Parcial' : 'Usado') }}
                                </span>
                            </td>
                        </tr>
                    </table>

                    <div class="title">Facturas Pagadas / Usos</div>
                    @if(($record->payments && $record->payments->count() > 0) || ($record->salePaymentDetails && $record->salePaymentDetails->count() > 0))
                        <table style="font-size: 10px;">
                            <thead>
                                <tr style="background-color: #f2f2f2;">
                                    <th style="width: 25%;">Fecha</th>
                                    <th style="width: 25%;">Factura</th>
                                    <th style="width: 30%;">Cliente</th>
                                    <th style="width: 20%;" class="text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($record->payments)
                                    @foreach($record->payments as $payment)
                                        <tr>
                                            <td>{{ $payment->created_at->format('d/m/Y') }}</td>
                                            <td>{{ $payment->sale->invoice_number ?? 'Abono' }}</td>
                                            <td>{{ $payment->sale->customer->name ?? 'N/A' }} <small style="color:#777;">(Abono)</small></td>
                                            <td class="text-right">${{ number_format($payment->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                                @if($record->salePaymentDetails)
                                    @foreach($record->salePaymentDetails as $paymentDetail)
                                        <tr>
                                            <td>{{ $paymentDetail->created_at->format('d/m/Y') }}</td>
                                            <td>{{ $paymentDetail->sale->invoice_number ?? 'Contado' }}</td>
                                            <td>{{ $paymentDetail->sale->customer->name ?? 'Consumidor' }} <small style="color:#28a745;">(Contado)</small></td>
                                            <td class="text-right">${{ number_format($paymentDetail->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    @else
                        <div style="text-align: center; padding: 15px; border: 1px solid #eee; color: #777;">
                            Este pago aún no ha sido asignado a ninguna venta o abono.
                        </div>
                    @endif
                </div>

                <div class="col-right">
                    <div class="title" style="text-align: center;">Comprobante de Pago Subido:</div>
                    @php
                        $fullPath = null;
                        if (!empty($record->image_path)) {
                            if (file_exists(public_path('storage/' . $record->image_path))) {
                                $fullPath = public_path('storage/' . $record->image_path);
                            } elseif (file_exists(storage_path('app/public/' . $record->image_path))) {
                                $fullPath = storage_path('app/public/' . $record->image_path);
                            } elseif (file_exists($record->image_path)) {
                                $fullPath = $record->image_path;
                            }
                        }
                    @endphp

                    @if($fullPath)
                        <div class="usdt-image-container">
                            <img src="{{ $fullPath }}" class="usdt-image" alt="Comprobante USDT">
                        </div>
                    @else
                        <div class="no-image">
                            No hay imagen adjunta para este comprobante USDT
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="page">
            <div class="header">
                <div class="company-name">{{ config('app.name', 'JSPOS') }}</div>
                <div style="font-size: 11px; margin-top: 3px; color: #555;">Reporte de Capturas USDT ($)</div>
            </div>
            <div class="no-image">No se encontraron registros de pagos USDT en el filtro seleccionado.</div>
        </div>
    @endforelse
</body>
</html>
