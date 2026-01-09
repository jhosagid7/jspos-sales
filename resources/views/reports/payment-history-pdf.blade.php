<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Historial de Pagos</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

        <style type="text/css" media="screen">
            html {
                font-family: sans-serif;
                line-height: 1.15;
                margin: 0;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
                font-weight: 400;
                line-height: 1.5;
                color: #212529;
                text-align: left;
                background-color: #fff;
                font-size: 11px;
                margin: 36pt;
            }

            table {
                border-collapse: collapse;
                width: 100%;
            }

            th {
                text-align: inherit;
            }

            .table {
                width: 100%;
                margin-bottom: 1rem;
                color: #212529;
            }

            .table th,
            .table td {
                padding: 0.5rem;
                vertical-align: top;
                border: 1px solid #dee2e6;
            }

            .table thead th {
                vertical-align: bottom;
                border-bottom: 2px solid #dee2e6;
                background-color: #f8f9fa;
                font-weight: bold;
            }

            .text-right {
                text-align: right !important;
            }

            .text-center {
                text-align: center !important;
            }

            .text-green {
                color: #28a745;
            }

            .text-red {
                color: #dc3545;
            }

            .header-info {
                margin-bottom: 20px;
                border-bottom: 2px solid #ccc;
                padding-bottom: 10px;
            }
            
            .logo-container {
                text-align: center;
                margin-bottom: 10px;
            }

            .summary-box {
                background-color: #f8f9fa;
                padding: 15px;
                margin-top: 20px;
                border: 1px solid #dee2e6;
            }

            .summary-row {
                display: flex;
                justify-content: space-between;
                padding: 5px 0;
            }

            .summary-label {
                font-weight: bold;
            }

            .total-row {
                border-top: 2px solid #000;
                margin-top: 10px;
                padding-top: 10px;
                font-size: 1.2em;
                font-weight: bold;
            }
        </style>
    </head>

            .invoice-title {
                color: #0380b2;
                font-weight: bold;
                font-size: 20px;
                margin: 0;
            }
            .report-title {
                color: #0380b2;
                font-size: 16px;
                font-weight: bold;
                margin: 0;
            }
            .box-details {
                border: 1px solid #6B7280;
                border-radius: 15px;
                padding: 10px;
                margin-bottom: 20px;
            }
            .text-blue {
                color: #0380b2;
            }
        </style>
    </head>

    <body>
        {{-- Header --}}
        <table class="table mt-1" style="margin-bottom: 0;">
            <tbody>
                <tr>
                    <td class="pl-0 border-0" width="25%" style="vertical-align: middle;">
                       @if($config->logo)
                            <img src="{{ public_path('storage/' . $config->logo) }}" alt="logo" height="60">
                        @endif
                    </td>
                    <td class="border-0 text-center" width="50%" style="vertical-align: middle;">
                        <h4 class="text-uppercase invoice-title">
                            {{ $config->business_name }}
                        </h4>
                    </td>
                    <td class="border-0 text-right" width="25%" style="vertical-align: middle;">
                        <h4 class="text-uppercase report-title">
                            HISTORIAL DE PAGOS
                        </h4>
                        <span style="font-size: 10px; font-weight: bold;">REPORTE</span>
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Info Box --}}
        <div class="box-details">
            <table class="table border-0" style="margin: 0;">
                <tbody>
                    <tr>
                        {{-- Business Info (Left) --}}
                        <td class="border-0 pl-0" width="50%" style="vertical-align: top;">
                            <strong class="text-uppercase" style="font-size: 14px;">{{ $config->business_name }}</strong><br>
                            NIT: {{ $config->taxpayer_id }}<br>
                            {{ $config->address }}
                        </td>

                        {{-- Sale/Client Info (Right) --}}
                        <td class="border-0 text-right pr-0" width="50%" style="vertical-align: top;">
                            Factura: <strong>{{ $sale->invoice_number ?? $sale->id }}</strong><br>
                            Fecha Emisión: <strong>{{ $sale->created_at->format('d/m/Y') }}</strong><br>
                            Cliente: <strong>{{ $sale->customer->name }}</strong>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h3>Detalle de Pagos</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Método</th>
                    <th>Moneda</th>
                    <th class="text-right">Monto Original</th>
                    <th class="text-right">Tasa</th>
                    <th class="text-right">Equiv. USD</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($payment->created_at)->format('d/m/Y H:i') }}</td>
                        <td>
                            @if($payment->pay_way == 'cash') Efectivo
                            @elseif($payment->pay_way == 'deposit') Banco
                            @elseif($payment->pay_way == 'nequi') Nequi
                            @else {{ ucfirst($payment->pay_way) }}
                            @endif
                            @if($payment->pay_way == 'deposit' && $payment->bank)
                                <br><small>{{ $payment->bank }}</small>
                            @endif
                        </td>
                        <td>{{ $payment->currency }}</td>
                        <td class="text-right">{{ number_format($payment->amount, 2) }}</td>
                        <td class="text-right">{{ number_format($payment->exchange_rate, 4) }}</td>
                        <td class="text-right">${{ number_format($payment->amount / ($payment->exchange_rate > 0 ? $payment->exchange_rate : 1), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary-box">
            <div class="summary-row">
                <span class="summary-label">Total Venta (USD):</span>
                <span>${{ number_format($totalSaleUSD, 2) }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Total Pagado (USD):</span>
                <span class="text-green">${{ number_format($totalPaidUSD, 2) }}</span>
            </div>
            @if($primaryCurrency)
            <div class="summary-row">
                <span class="summary-label">Total Pagado ({{ $primaryCurrency->code }}):</span>
                <span class="text-green">{{ $primaryCurrency->symbol }}{{ number_format($totalPaidPrimary, 2) }}</span>
            </div>
            @endif
            <div class="summary-row total-row">
                <span class="summary-label">Saldo Pendiente:</span>
                <span class="{{ $balanceUSD > 0 ? 'text-red' : 'text-green' }}">
                    @if($primaryCurrency)
                        {{ $primaryCurrency->symbol }}{{ number_format($balancePrimary, 2) }}
                    @else
                        ${{ number_format($balanceUSD, 2) }}
                    @endif
                </span>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: center; font-size: 9px; color: #666;">
            <p>Este reporte fue generado automáticamente el {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
        </div>
    </body>
</html>
