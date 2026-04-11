<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Relación de Cobros por Cliente</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            width: 100%;
            margin-bottom: 10px;
        }
        .header td {
            vertical-align: top;
        }
        .business-info h2 {
            margin: 0;
            font-size: 14pt;
        }
        .report-info {
            text-align: right;
        }
        .report-title {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #f2f2f2;
            border-bottom: 2px solid #ddd;
            padding: 3px;
            text-align: left;
            font-size: 7pt;
        }
        .table td {
            padding: 2px;
            border-bottom: 1px solid #eee;
            font-size: 7pt;
            vertical-align: top;
        }
        .customer-header {
            background-color: #d9d9d9;
            font-weight: bold;
            padding: 5px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row td {
            font-weight: bold;
            border-top: 2px solid #333;
        }
        .footer-totals {
            width: 100%;
            margin-top: 20px;
        }
        .footer-totals td {
            vertical-align: top;
        }
        .summary-box {
            width: 100%;
        }
        .summary-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-box td {
            padding: 3px;
        }
        .summary-label {
            font-weight: bold;
        }
        .nc-row {
            color: #d9534f;
        }
    </style>
</head>
<body>

    <table class="header">
        <tr>
            <td class="business-info" width="60%">
                <h2>{{ $config->business_name }}</h2>
                <p>
                    {{ $config->address }}<br>
                    {{ $config->phone }}<br>
                    {{ $config->taxpayer_id }}
                </p>
            </td>
            <td class="report-info">
                <p>
                    Fecha : {{ date('d/m/Y') }}<br>
                    Hora : {{ date('h:i a') }}<br>
                    Pág : 1
                </p>
            </td>
        </tr>
    </table>

    <div class="report-title">RELACIÓN DE COBROS POR CLIENTE</div>
    
    <div style="margin-bottom: 15px; font-size: 9pt; line-height: 1.4;">
        <strong>Filtros aplicados:</strong><br>
        Rango de Fechas : {{ Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}<br>
        @if($customer_id)
            @php $filteredCustomer = \App\Models\Customer::find($customer_id); @endphp
            Filtrado por : {{ $filteredCustomer ? $filteredCustomer->name : $customer_id }}<br>
        @else
            Filtrado por : Todos los clientes<br>
        @endif
        Generado por : {{ $user->name }}
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Operación</th>
                <th>Fecha Pago</th>
                <th>Fecha Emisión</th>
                <th class="text-center">Días</th>
                <th>No. Documento</th>
                <th>Descripción</th>
                <th class="text-right">Monto (USD)</th>
                <th class="text-right">Total Ingreso</th>
            </tr>
        </thead>
        <tbody>
            @foreach($grouped as $customerId => $sales)
                @php
                    $firstCust = $sales->first()->first();
                    $custMonto = 0;
                    $custIngreso = 0;
                @endphp
                <tr>
                    <td colspan="8" class="customer-header">
                        {{ $firstCust['customer_doc'] }} - {{ strtoupper($firstCust['customer_name']) }} (#{{ $firstCust['customer_id'] }})
                    </td>
                </tr>
                @foreach($sales as $saleId => $items)
                    @php
                        $subFirst = $items->first();
                        $subtotalMonto = $items->sum('monto');
                        $subtotalIngreso = $items->sum('ingreso');
                        $custMonto += $subtotalMonto;
                        $custIngreso += $subtotalIngreso;
                    @endphp
                    <tr style="background-color: #f9f9f9;">
                        <td colspan="8" style="padding-left: 10px; border-bottom: 1px solid #ddd;">
                            <strong>Documento: {{ $subFirst['doc_number'] }}</strong>
                            <span style="font-size: 8pt; margin-left: 20px;">Emisión: {{ $subFirst['date_emit']->format('d/m/Y') }}</span>
                        </td>
                    </tr>
                    @foreach($items as $item)
                        <tr class="{{ $item['type'] == 'N/C' ? 'nc-row' : '' }}">
                            <td style="padding-left: 15px;">{{ $item['type'] }}</td>
                            <td>{{ $item['date_pay']->format('d/m/Y') }}</td>
                            <td>{{ $item['date_emit']->format('d/m/Y') }}</td>
                            <td class="text-center">{{ $item['days'] }}</td>
                            <td>{{ $item['doc_number'] }}</td>
                            <td>{{ $item['description'] }}</td>
                            <td class="text-right">{{ number_format($item['monto'], 4) }}</td>
                            <td class="text-right">{{ number_format($item['ingreso'], 4) }}</td>
                        </tr>
                    @endforeach
                    <tr style="border-bottom: 1px dashed #ccc;">
                        <td colspan="6" class="text-right" style="font-size: 7pt; font-style: italic;">Subtotal {{ $subFirst['doc_number'] }}:</td>
                        <td class="text-right" style="border-top: 1px solid #ccc;"><strong>{{ number_format($subtotalMonto, 4) }}</strong></td>
                        <td class="text-right" style="border-top: 1px solid #ccc;"><strong>{{ number_format($subtotalIngreso, 4) }}</strong></td>
                    </tr>
                @endforeach
                <tr style="background-color: #eee;">
                    <td colspan="6" class="text-right"><strong>TOTAL {{ strtoupper($firstCust['customer_name']) }}:</strong></td>
                    <td class="text-right" style="border-top: 2px solid #333;"><strong>{{ number_format($custMonto, 4) }}</strong></td>
                    <td class="text-right" style="border-top: 2px solid #333;"><strong>{{ number_format($custIngreso, 4) }}</strong></td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="6" class="text-right">TOTAL GENERAL:</td>
                <td class="text-right">{{ number_format($totalMonto, 4) }}</td>
                <td class="text-right">{{ number_format($totalIngreso, 4) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer-totals">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                {{-- Column 1: Income Categories --}}
                <td style="width: 35%; padding-right: 15px;">
                    <table style="width: 100%; border: 1px solid #ccc;">
                        <thead>
                            <tr style="background-color: #eee;">
                                <th colspan="2" class="text-center" style="font-size: 7pt; padding: 2px;">Resumen por Canal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($summary as $item)
                                <tr>
                                    <td class="summary-label" style="font-size: 7pt; border-bottom: 1px solid #eee;">{{ $item['name'] }}:</td>
                                    <td class="text-right" style="font-size: 8pt; border-bottom: 1px solid #eee;">{{ number_format($item['equiv'], 4) }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td class="summary-label" style="font-size: 7pt; background: #eee;">Total Ingreso USD:</td>
                                <td class="text-right" style="font-weight: bold; font-size: 8pt; background: #eee;">{{ number_format($totalIngreso, 4) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>

                {{-- Column 2: Detalle por Moneda --}}
                <td style="width: 30%; padding: 0 15px; vertical-align: top;">
                    <table style="width: 100%; border: 1px solid #ccc;">
                        <thead>
                            <tr style="background-color: #eee;">
                                <th colspan="2" class="text-center" style="font-size: 7pt; padding: 2px;">Totales por Moneda (Original)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($totalsByCurrency as $curr => $amt)
                                <tr>
                                    <td class="summary-label" style="font-size: 7pt; border-bottom: 1px solid #eee;">Total {{ $curr }}:</td>
                                    <td class="text-right" style="font-size: 8pt; border-bottom: 1px solid #eee;">{{ number_format($amt, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>

                {{-- Column 3: Signatures --}}
                <td style="width: 35%; padding-left: 15px; vertical-align: bottom;">
                    <div style="text-align: center; margin-bottom: 50px;">
                        <div style="border-top: 1px solid #000; width: 150px; margin: 0 auto;"></div>
                        <div style="font-size: 7pt; margin-top: 3px;">
                            <strong>ENTREGADO POR</strong><br>
                            (OPERADOR)
                        </div>
                    </div>

                    <div style="text-align: center;">
                        <div style="border-top: 1px solid #000; width: 150px; margin: 0 auto;"></div>
                        <div style="font-size: 7pt; margin-top: 3px;">
                            <strong>RECIBIDO POR</strong>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
