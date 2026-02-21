<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago - Factura #{{ $sale->id }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #333; line-height: 1.4; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #3498db; padding-bottom: 15px; }
        .company-name { font-size: 22px; font-weight: bold; color: #2c3e50; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .company-info { font-size: 11px; color: #7f8c8d; }
        .receipt-title { font-size: 16px; font-weight: bold; color: #3498db; margin-top: 15px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .info-table { width: 100%; margin-bottom: 25px; border-collapse: collapse; }
        .info-table td { padding: 8px; vertical-align: top; border: 1px solid #ecf0f1; background-color: #fcfcfc; }
        .info-label { font-size: 9px; color: #7f8c8d; text-transform: uppercase; display: block; margin-bottom: 3px; }
        .info-value { font-size: 12px; font-weight: bold; color: #2c3e50; }
        
        .payment-table { width: 100%; border-collapse: collapse; margin-top: 15px; border-radius: 5px; overflow: hidden; border: 1px solid #bdc3c7; }
        .payment-table th { background-color: #ecf0f1; color: #2c3e50; font-weight: bold; padding: 10px 8px; text-align: left; font-size: 10px; text-transform: uppercase; border-bottom: 2px solid #bdc3c7; }
        .payment-table td { border-bottom: 1px solid #ecf0f1; padding: 10px 8px; font-size: 11px; vertical-align: middle; }
        .payment-table tr:nth-child(even) { background-color: #f9f9f9; }
        .payment-table tr:last-child td { border-bottom: none; }
        
        .totals-container { width: 100%; margin-top: 30px; clear: both; }
        .totals-table { width: 45%; float: right; border-collapse: collapse; border: 1px solid #bdc3c7; background-color: #fcfcfc; }
        .totals-table td { padding: 8px 12px; text-align: right; border-bottom: 1px solid #ecf0f1; }
        .totals-table tr:last-child td { border-bottom: none; font-size: 14px; font-weight: bold; color: #2c3e50; background-color: #ecf0f1; }
        .totals-table .label { font-weight: bold; color: #7f8c8d; font-size: 10px; text-transform: uppercase; text-align: left; }
        .totals-table .value { font-weight: bold; color: #333; }
        
        .status-badge { padding: 3px 8px; border-radius: 12px; color: white; font-size: 9px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .status-pending { background-color: #f39c12; }
        .status-approved { background-color: #27ae60; }
        .status-rejected { background-color: #e74c3c; }
        .voided { text-decoration: line-through; color: #95a5a6; }
        
        .footer { clear: both; margin-top: 60px; text-align: center; font-size: 9px; color: #95a5a6; border-top: 1px solid #ecf0f1; padding-top: 15px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .logo-container { margin-bottom: 15px; }
        .logo { max-height: 70px; max-width: 250px; }
    </style>
</head>
<body>
    <div class="header">
        @if($config->logo && file_exists(public_path('storage/' . $config->logo)))
            <div class="logo-container">
                <img src="{{ public_path('storage/' . $config->logo) }}" class="logo" alt="Logo">
            </div>
        @endif
        <div class="company-name">{{ $config->business_name }}</div>
        <div class="company-info">{{ $config->address }} &bull; NIT: {{ $config->taxpayer_id }} &bull; Tel: {{ $config->phone }}</div>
        <div class="receipt-title">RECIBO DE PAGO #{{ $sale->id }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td width="50%">
                <span class="info-label">CLIENTE</span>
                <span class="info-value">{{ $sale->customer->name }}</span><br>
                <span style="color: #7f8c8d; font-size: 10px;">{{ $sale->customer->phone }}<br>
                {{ $sale->customer->address }}</span>
            </td>
            <td width="50%" style="text-align: right;">
                <span class="info-label">DETALLES DE LA TRANSACCIÓN</span>
                <span class="info-value">FOLIO: {{ $sale->id }}</span><br>
                <span style="color: #7f8c8d; font-size: 10px;">FECHA EMISIÓN: {{ \Carbon\Carbon::parse($sale->created_at)->format('d/m/Y H:i A') }}<br>
                VENDEDOR: {{ $sale->user->name }}</span>
            </td>
        </tr>
    </table>

    <table class="payment-table">
        <thead>
            <tr>
                <th>FECHA</th>
                <th>MÉTODO</th>
                <th>DETALLES</th>
                <th>ESTADO</th>
                <th class="text-center">MONEDA</th>
                <th class="text-right">MONTO ORIG.</th>
                <th class="text-right">TASA</th>
                <th class="text-right">MONTO USD</th>
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
                        $details[] = "<b>Ref:</b> " . ($payment->zelleRecord->reference ?? 'NA');
                    } elseif ($payment->pay_way == 'deposit' || $payment->pay_way == 'bank') {
                         if ($payment->bankRecord) {
                             $bankName = $payment->bankRecord->bank->name ?? 'Banco';
                             $details[] = "<b>Banco:</b> " . $bankName;
                             $details[] = "<b>Fecha:</b> " . \Carbon\Carbon::parse($payment->bankRecord->payment_date)->format('d/m/Y');
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
                        <strong>{{ ucfirst($payment->pay_way == 'cash' ? 'Efectivo' : ($payment->pay_way == 'deposit' ? 'Banco' : $payment->pay_way)) }}</strong>
                    </td>
                    <td style="text-align: left; font-size: 10px; color: #7f8c8d;">
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
                            <span class="status-badge status-rejected">{{ strtoupper($payment->status) }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $payment->currency }}</td>
                    <td class="text-right">{{ number_format($payment->amount, 2) }}</td>
                    <td class="text-right" style="color: #7f8c8d;">{{ number_format($rate, 2) }}</td>
                    <td class="text-right"><strong>$ {{ number_format($amountUSD, 2) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-container">
        <table class="totals-table">
            <tr>
                <td class="label">TOTAL VENTA (USD):</td>
                <td class="value">$ {{ number_format($sale->total_usd > 0 ? $sale->total_usd : ($sale->total / ($sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1)), 2) }}</td>
            </tr>
            <tr>
                <td class="label">TOTAL ABONADO (USD):</td>
                <td class="value">$ {{ number_format($totalPaidUSD, 2) }}</td>
            </tr>
            <tr>
                <td class="label">SALDO RESTANTE (USD):</td>
                <td class="value">$ {{ number_format(max(0, ($sale->total_usd > 0 ? $sale->total_usd : ($sale->total / ($sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1))) - $totalPaidUSD), 2) }}</td>
            </tr>
        </table>
    </div>
    
    <div class="footer">
        Este documento es un comprobante de pago emitido electrónicamente.<br>
        Generado el: {{ date('d/m/Y h:i A') }} por {{ auth()->user()->name ?? 'Sistema' }}
    </div>
</body>
</html>
