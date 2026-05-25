<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Capturas Zelle Filtradas</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; }
        .page { page-break-after: always; padding: 10px; }
        .page:last-child { page-break-after: avoid; }
        .header { width: 100%; text-align: center; margin-bottom: 15px; border-bottom: 2px solid #3B3F5C; padding-bottom: 10px; }
        .company-name { font-size: 16px; font-weight: bold; color: #3B3F5C; }
        .title { font-size: 13px; font-weight: bold; margin-top: 10px; margin-bottom: 10px; background-color: #f2f2f2; padding: 5px 10px; border-left: 3px solid #3B3F5C; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ddd; padding: 5px 8px; text-align: left; }
        th { background-color: #f9f9f9; font-weight: bold; width: 35%; }
        .text-right { text-align: right; }
        .zelle-image-container { text-align: center; margin-top: 15px; }
        .zelle-image { max-width: 90%; max-height: 480px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; box-shadow: 1px 1px 5px rgba(0,0,0,0.1); }
        .no-image { text-align: center; padding: 40px; border: 1px dashed #ccc; border-radius: 4px; background-color: #fafafa; font-size: 12px; color: #777; margin-top: 15px; }
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
                    Reporte de Capturas Zelle ({{ $index + 1 }} de {{ count($records) }})
                </div>
            </div>

            <div class="grid-container clearfix">
                <div class="col-left">
                    <div class="title">Datos del Pago</div>
                    <table>
                        <tr>
                            <th>Fecha</th>
                            <td>{{ $record->zelle_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <th>Referencia</th>
                            <td><strong>{{ $record->reference }}</strong></td>
                        </tr>
                        <tr>
                            <th>Remitente</th>
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
                                <span style="text-transform: uppercase; font-weight: bold; color: {{ $record->status == 'unused' ? '#6c757d' : ($record->status == 'partial' ? '#fd7e14' : '#28a745') }}">
                                    {{ $record->status == 'unused' ? 'Sin Usar' : ($record->status == 'partial' ? 'Parcial' : 'Usado') }}
                                </span>
                            </td>
                        </tr>
                    </table>

                    <div class="title">Facturas Pagadas / Usos</div>
                    @if($record->payments->count() > 0 || $record->salePaymentDetails->count() > 0)
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
                                @foreach($record->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->created_at->format('d/m/Y') }}</td>
                                        <td>{{ $payment->sale->invoice_number ?? 'Abono' }}</td>
                                        <td>{{ $payment->sale->customer->name ?? 'N/A' }} <small style="color:#777;">(Abono)</small></td>
                                        <td class="text-right">${{ number_format($payment->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                                @foreach($record->salePaymentDetails as $paymentDetail)
                                    <tr>
                                        <td>{{ $paymentDetail->created_at->format('d/m/Y') }}</td>
                                        <td>{{ $paymentDetail->sale->invoice_number ?? 'Contado' }}</td>
                                        <td>{{ $paymentDetail->sale->customer->name ?? 'Consumidor' }} <small style="color:#28a745;">(Contado)</small></td>
                                        <td class="text-right">${{ number_format($paymentDetail->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div style="text-align: center; padding: 15px; border: 1px solid #eee; color: #777;">
                            Este pago aún no ha sido asignado a ninguna venta o abono.
                        </div>
                    @endif
                </div>

                <div class="col-right">
                    <div class="title" style="text-align: center;">Captura de Pantalla / Comprobante</div>
                    @if($record->image_path && file_exists(public_path('storage/' . $record->image_path)))
                        <div class="zelle-image-container">
                            <img src="{{ public_path('storage/' . $record->image_path) }}" class="zelle-image">
                        </div>
                    @else
                        <div class="no-image">
                            <i class="fas fa-image" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                            Sin Imagen de Captura Adjunta
                        </div>
                    @endif
                </div>
            </div>

            <div style="position: absolute; bottom: 15px; width: 95%; font-size: 9px; color: #888; text-align: center; border-top: 1px solid #eee; padding-top: 5px;">
                Generado el {{ date('d/m/Y H:i:s') }} por {{ auth()->user()->name }} | JSPOS Sales
            </div>
        </div>
    @empty
        <div style="text-align: center; padding: 50px; font-size: 14px; color: #777;">
            No hay registros Zelle que coincidan con los filtros seleccionados.
        </div>
    @endforelse
</body>
</html>
