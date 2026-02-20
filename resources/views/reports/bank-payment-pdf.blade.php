<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Pago Bancario</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { width: 100%; text-align: center; margin-bottom: 20px; }
        .company-name { font-size: 18px; font-weight: bold; }
        .title { font-size: 16px; font-weight: bold; margin-bottom: 15px; border-bottom: 2px solid #ddd; padding-bottom: 5px; }
        .section { margin-bottom: 20px; }
        .row { width: 100%; clear: both; }
        .col-half { width: 48%; float: left; }
        .col-full { width: 100%; clear: both; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .zelle-image { max-width: 100%; max-height: 400px; border: 1px solid #ccc; display: block; margin: 0 auto; }
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('app.name', 'JSPOS') }}</div>
        <div>Reporte de Pago Bancario</div>
    </div>

    <div class="title">Datos del Depósito</div>

    <div class="section clearfix">
        <div class="col-half">
            <table>
                <tr>
                    <th>Banco</th>
                    <td>{{ $record->bank->name }} ({{ $record->bank->currency_code }})</td>
                </tr>
                <tr>
                    <th>Fecha</th>
                    <td>{{ $record->payment_date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <th>Referencia</th>
                    <td>{{ $record->reference }}</td>
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
                    <td>{{ ucfirst($record->status) }}</td>
                </tr>
                 <tr>
                    <th>Nota</th>
                    <td>{{ $record->note }}</td>
                </tr>
            </table>
        </div>
        <div class="col-half" style="margin-left: 4%;">
             @if($record->image_path)
                <div style="text-align: center;">
                    <strong>Comprobante Adjunto</strong><br>
                    <img src="{{ public_path('storage/' . $record->image_path) }}" class="zelle-image">
                </div>
            @else
                <div style="text-align: center; padding: 20px; border: 1px dashed #ccc;">
                    Sin Imagen
                </div>
            @endif
        </div>
    </div>

    <div class="title">Facturas Pagadas / Usos</div>
    <div class="section">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Factura #</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th class="text-right">Monto Usado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($record->payments as $payment)
                    <tr>
                        <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @if($payment->sale)
                                {{ $payment->sale->invoice_number ?? $payment->sale->id }}
                            @else
                                N/A (Abono/Crédito)
                            @endif
                        </td>
                        <td>
                            @if($payment->sale)
                                {{ $payment->sale->customer->name ?? 'Consumidor Final' }}
                            @elseif($payment->user)
                                {{ $payment->user->name }} (Usuario)
                            @endif
                        </td>
                         <td>
                            @if($payment->sale && $payment->sale->user)
                                {{ $payment->sale->user->name }}
                            @elseif($payment->user)
                                {{ $payment->user->name }}
                            @endif
                        </td>
                        <td class="text-right">${{ number_format($payment->amount, 2) }}</td>
                    </tr>
                @endforeach
                @foreach($record->salePaymentDetails as $paymentDetail)
                    <tr>
                        <td>{{ $paymentDetail->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @if($paymentDetail->sale)
                                {{ $paymentDetail->sale->invoice_number ?? $paymentDetail->sale->id }}
                            @else
                                N/A (Venta Contado)
                            @endif
                        </td>
                        <td>
                            @if($paymentDetail->sale && $paymentDetail->sale->customer)
                                {{ $paymentDetail->sale->customer->name }} (Contado)
                            @else
                                Consumidor Final (Contado)
                            @endif
                        </td>
                         <td>
                            @if($paymentDetail->sale && $paymentDetail->sale->user)
                                {{ $paymentDetail->sale->user->name }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="text-right">${{ number_format($paymentDetail->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top: 30px; font-size: 10px; color: #777; text-align: center;">
        Generado el {{ date('d/m/Y H:i:s') }} por {{ auth()->user()->name }}
    </div>
</body>
</html>
