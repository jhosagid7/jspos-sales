<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Cuentas Por Cobrar</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

        <style type="text/css" media="screen">
            html {
                font-family: sans-serif;
                line-height: 1.15;
                margin: 0;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
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
                padding: 0.3rem;
                vertical-align: top;
                border: none;
            }

            .text-right {
                text-align: right !important;
            }

            .text-center {
                text-align: center !important;
            }

            .text-uppercase {
                text-transform: uppercase !important;
            }

            .text-green {
                color: #77b632;
            }

            .text-red {
                color: #ea5340;
            }

            .customer-header {
                background-color: #e9ecef;
                font-weight: bold;
                padding: 5px;
                margin-top: 10px;
                border-bottom: 2px solid #dee2e6;
            }

            .invoice-row {
                font-weight: bold;
                background-color: #f8f9fa;
                border-top: 1px solid #dee2e6;
            }
            
            .payment-row {
                font-style: italic;
                color: #555;
            }
            
            .payment-detail {
                padding-left: 20px !important;
            }

            .grand-total {
                font-size: 1.1rem;
                font-weight: bold;
                background-color: #28a745;
                color: white;
                padding: 10px;
                margin-top: 20px;
            }

            .header-info {
                margin-bottom: 20px;
                border-bottom: 1px solid #ccc;
                padding-bottom: 10px;
            }
            
            .logo-container {
                text-align: center;
                margin-bottom: 10px;
            }
        </style>
    </head>

    <body>
        <div class="logo-container">
             @if($config->logo)
                <img src="{{ public_path('storage/' . $config->logo) }}" alt="logo" height="50">
            @else
                <h2>{{ strtoupper($config->business_name) }}</h2>
            @endif
        </div>

        <div class="header-info">
            <table class="table">
                <tr>
                    <td>
                        <strong>Reporte Cuentas Por Cobrar</strong><br>
                        @if($seller_name)
                            <strong class="text-green">VENDEDOR: {{ strtoupper($seller_name) }}</strong><br>
                        @endif
                        <strong>Fecha Reporte:</strong> {{ $date }}<br>
                        <strong>Generado por:</strong> {{ $user->name }}
                    </td>
                    <td class="text-right">
                        <strong>{{ $config->business_name }}</strong><br>
                        {{ $config->address }}<br>
                        NIT: {{ $config->taxpayer_id }}
                    </td>
                </tr>
            </table>
        </div>

        <table class="table">
            <tbody>
                @foreach($data as $key => $groupData)
                    @if($groupBy != 'none')
                        <tr>
                            <td colspan="6" class="customer-header">
                                {{ strtoupper($groupData['name']) }}
                            </td>
                        </tr>
                    @endif
                    
                    @foreach($groupData['invoices'] as $invoice)
                        <tr class="invoice-row">
                            <td colspan="6">
                                Factura #{{ $invoice['folio'] }} | 
                                Emisión: {{ $invoice['date'] }} | 
                                Vencimiento: {{ $invoice['due_date'] }} | 
                                Total: ${{ number_format($invoice['total'], 2) }} | 
                                Abono: ${{ number_format($invoice['total'] - $invoice['balance'], 2) }} | 
                                Saldo: <span class="text-red">${{ number_format($invoice['balance'], 2) }}</span>
                            </td>
                        </tr>
                        
                        @if(count($invoice['payments']) > 0)
                            <tr>
                                <th class="payment-detail">Fecha Pago</th>
                                <th>Método</th>
                                <th>Moneda</th>
                                <th class="text-right">Monto Orig.</th>
                                <th class="text-right">Tasa</th>
                                <th class="text-right">Equiv. USD</th>
                            </tr>
                            @foreach($invoice['payments'] as $payment)
                                <tr class="payment-row">
                                    <td class="payment-detail">{{ $payment['date'] }}</td>
                                    <td>
                                        @if($payment['method'] == 'cash') Efectivo
                                        @elseif($payment['method'] == 'deposit') Banco
                                        @else {{ ucfirst($payment['method']) }}
                                        @endif
                                    </td>
                                    <td>{{ $payment['currency'] }}</td>
                                    <td class="text-right">{{ number_format($payment['amount_original'], 2) }}</td>
                                    <td class="text-right">{{ number_format($payment['rate'], 2) }}</td>
                                    <td class="text-right">${{ number_format($payment['amount_usd'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr><td colspan="6" style="border-bottom: 1px solid #ddd;"></td></tr>
                        @else
                            <tr>
                                <td colspan="6" class="payment-detail text-center text-red">Sin pagos registrados</td>
                            </tr>
                            <tr><td colspan="6" style="border-bottom: 1px solid #ddd;"></td></tr>
                        @endif
                        
                    @endforeach

                    @if($groupBy != 'none')
                        <tr>
                            <td colspan="5" class="text-right" style="font-weight: bold; padding-top: 10px;">TOTAL DEUDA GRUPO:</td>
                            <td class="text-right" style="font-weight: bold; padding-top: 10px;">${{ number_format($groupData['total_debt'], 2) }}</td>
                        </tr>
                        <tr><td colspan="6">&nbsp;</td></tr>
                    @endif

                @endforeach
            </tbody>
        </table>
        
        <div class="grand-total text-center">
            TOTAL GENERAL DEUDA PENDIENTE: ${{ number_format($grandTotalDebt, 2) }}
        </div>

        <div style="margin-top: 30px; text-align: center; font-size: 9px; color: #666;">
            <p>Este reporte fue generado automáticamente por el sistema.</p>
        </div>
    </body>
</html>
