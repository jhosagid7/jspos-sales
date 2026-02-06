<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Pagos - Factura #{{ $sale->id }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .company-name { font-size: 14px; font-weight: bold; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 3px; vertical-align: top; }
        .payment-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .payment-table th, .payment-table td { border: 1px solid #ddd; padding: 5px; text-align: center; }
        .payment-table th { background-color: #f2f2f2; font-weight: bold; }
        .totals { margin-top: 20px; text-align: right; }
        .totals-table { width: 40%; float: right; border-collapse: collapse; }
        .totals-table td { padding: 3px; text-align: right; }
        .totals-table .label { font-weight: bold; }
        .status-badge { padding: 2px 5px; border-radius: 3px; color: white; font-size: 9px; }
        .status-pending { background-color: #f39c12; }
        .status-approved { background-color: #27ae60; }
        .voided { text-decoration: line-through; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $config->business_name }}</div>
        <div>{{ $config->address }}</div>
        <div>NIT: {{ $config->taxpayer_id }}</div>
        <div>Tel: {{ $config->phone }}</div>
    </div>

    <div style="clear: both; margin-bottom: 15px; border-bottom: 1px solid #000;"></div>

    <table class="info-table">
        <tr>
            <td width="50%">
                <strong>CLIENTE:</strong><br>
                {{ $sale->customer->name }}<br>
                {{ $sale->customer->phone }}<br>
                {{ $sale->customer->address }}
            </td>
            <td width="50%" style="text-align: right;">
                <strong>FOLIO VENTA:</strong> #{{ $sale->id }}<br>
                <strong>FECHA EMISIÓN:</strong> {{ \Carbon\Carbon::parse($sale->created_at)->format('d/m/Y H:i') }}<br>
                <strong>VENDEDOR:</strong> {{ $sale->user->name }}
            </td>
        </tr>
    </table>

    <h3 style="text-align: center; margin-top: 5px; margin-bottom: 5px;">HISTORIAL DE PAGOS</h3>

    <table class="payment-table">
        <thead>
            <tr>
                <th>FECHA</th>
                <th>MÉTODO</th>
                <th>DETALLES</th>
                <th>ESTADO</th>
                <th>MONEDA</th>
                <th>MONTO ORIG.</th>
                <th>TASA</th>
                <th>MONTO USD</th>
            </tr>
        </thead>
        <tbody>
            @php $totalPaidUSD = 0; @endphp
            @foreach($sale->payments as $payment)
                @php
                    $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                    $amountUSD = $payment->amount / $rate;
                    
                    if($payment->status === 'approved') {
                        $totalPaidUSD += $amountUSD;
                    }

                    // Prepare Detail Strings
                    $details = [];
                    
                    if ($payment->pay_way == 'zelle' && $payment->zelleRecord) {
                        $details[] = "<b>Emisor:</b> " . $payment->zelleRecord->sender_name;
                        $details[] = "<b>Fecha:</b> " . \Carbon\Carbon::parse($payment->zelleRecord->zelle_date)->format('d/m/Y');
                        $details[] = "<b>Monto Orig.:</b> $" . number_format($payment->zelleRecord->amount, 2);
                        $details[] = "<b>Ref:</b> " . ($payment->zelleRecord->reference ?? 'NA');
                    } elseif ($payment->pay_way == 'deposit' || $payment->pay_way == 'bank') {
                         if ($payment->bankRecord) {
                             $bankName = $payment->bankRecord->bank->name ?? 'Banco';
                             $details[] = "<b>Banco:</b> " . $bankName;
                             $details[] = "<b>Fecha:</b> " . \Carbon\Carbon::parse($payment->bankRecord->payment_date)->format('d/m/Y');
                             $details[] = "<b>Monto:</b> $" . number_format($payment->bankRecord->amount, 2);
                             $details[] = "<b>Ref:</b> " . ($payment->bankRecord->reference ?? 'NA');
                         } else {
                             // Fallback for old records or no record linked
                             if($payment->bank) $details[] = "<b>Banco:</b> " . $payment->bank;
                             if($payment->deposit_number) $details[] = "<b>Ref:</b> " . $payment->deposit_number;
                             if($payment->reference && empty($payment->deposit_number)) $details[] = "<b>Ref:</b> " . $payment->reference;
                         }
                    } else {
                        // Cash or others
                        if($payment->reference) $details[] = "Ref: " . $payment->reference;
                    }

                @endphp
                <tr class="{{ $payment->status === 'cancelled' ? 'voided' : '' }}">
                    <td>{{ \Carbon\Carbon::parse($payment->created_at)->format('d/m/Y') }}</td>
                    <td>
                        {{ ucfirst($payment->pay_way == 'cash' ? 'Efectivo' : ($payment->pay_way == 'deposit' ? 'Banco' : $payment->pay_way)) }}
                    </td>
                    <td style="text-align: left;">
                        @foreach($details as $line)
                            {!! $line !!}<br>
                        @endforeach
                    </td>
                    <td>
                        @if($payment->status == 'pending')
                            <span class="status-badge status-pending">PENDIENTE</span>
                        @elseif($payment->status == 'approved')
                            <span class="status-badge status-approved">APROBADO</span>
                        @else
                            {{ ucfirst($payment->status) }}
                        @endif
                    </td>
                    <td>{{ $payment->currency }}</td>
                    <td>{{ number_format($payment->amount, 2) }}</td>
                    <td>{{ number_format($rate, 2) }}</td>
                    <td>$ {{ number_format($amountUSD, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table class="totals-table">
            <tr>
                <td class="label">TOTAL VENTA (USD):</td>
                <td>$ {{ number_format($sale->total_usd > 0 ? $sale->total_usd : ($sale->total / ($sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1)), 2) }}</td>
            </tr>
            <tr>
                <td class="label">TOTAL PAGADO (USD):</td>
                <td>$ {{ number_format($totalPaidUSD, 2) }}</td>
            </tr>
            <tr>
                <td class="label">SALDO PENDIENTE (USD):</td>
                <td>$ {{ number_format(max(0, ($sale->total_usd > 0 ? $sale->total_usd : ($sale->total / ($sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1))) - $totalPaidUSD), 2) }}</td>
            </tr>
        </table>
    </div>
    
    <div style="clear: both; margin-top: 50px; text-align: center; font-size: 9px; color: #555;">
        Este documento es un reporte interno del historial de pagos.<br>
        Generado el: {{ date('d/m/Y H:i') }} por {{ auth()->user()->name }}
    </div>
</body>
</html>
